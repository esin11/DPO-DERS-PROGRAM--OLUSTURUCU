<?php
declare(strict_types=1);
namespace App\Service;

use App\Config\AppConfig;
use App\Entity\Course;
use App\Entity\Room;
use App\Entity\ScheduleEntry;
use App\Exception\NoSolutionException;
use App\Repository\AvailabilityRepository;
use App\Repository\CourseRepository;
use App\Repository\RoomRepository;
use App\Repository\ScheduleRepository;

/**
 * Kısıt tabanlı ders programı üretici — Backtracking algoritması.
 *
 * Algoritma özeti:
 *  1. Dersler haftada weekly_hours kadar slot ister.
 *     Her ders weekly_hours adet atama ihtiyacı üretilir (assignment list).
 *  2. Backtrack(index, assignments[]):
 *     a. index == son → çözüm bulundu, döndür.
 *     b. Mevcut atama için tüm (gün, slot, oda) kombinasyonlarını dene.
 *     c. Kısıtları kontrol et (hoca, oda, grup, kapasite, müsaitlik).
 *     d. Geçerliyse ata → Backtrack(index+1) → çözüm bulunursa döndür.
 *     e. Yoksa geri al, bir sonraki kombinasyonu dene.
 *  3. Hiç kombinasyon kalmadıysa null döndür → üste çıkar.
 */
class SchedulerService
{
    private int $iterationCount = 0;
    private float $startTime    = 0;

    /** @var ScheduleEntry[] Mevcut kısmi çözüm (backtracking sırasında) */
    private array $partial = [];

    public function __construct(
        private readonly CourseRepository       $courseRepo,
        private readonly RoomRepository         $roomRepo,
        private readonly AvailabilityRepository $availRepo,
        private readonly ScheduleRepository     $scheduleRepo,
        private readonly ConflictCheckerService $conflictChecker,
    ) {}

    // ─────────────────────────────────────────────────────────────────
    //  PUBLIC API
    // ─────────────────────────────────────────────────────────────────

    /**
     * Yeni bir program üretir, kaydeder ve üretilen entry listesini döner.
     * @return ScheduleEntry[]
     * @throws NoSolutionException Çözüm bulunamazsa
     */
    public function generate(): array
    {
        $this->iterationCount = 0;
        $this->startTime      = microtime(true);
        $this->partial        = [];

        $assignments = $this->buildAssignmentList();

        if (empty($assignments)) {
            throw new NoSolutionException(
                reasons: ['Sistemde hiç ders tanımlanmamış.']
            );
        }

        $result = $this->backtrack($assignments, 0);

        if ($result === null) {
            throw new NoSolutionException(
                reasons: $this->diagnoseFailure($assignments),
            );
        }

        // Yeni versiyonu kaydet
        $version = $this->scheduleRepo->getLatestVersion() + 1;
        $entries = [];
        foreach ($result as $i => $row) {
            $entries[] = new ScheduleEntry(
                id:              0,
                courseId:        $row['course_id'],
                roomId:          $row['room_id'],
                dayOfWeek:       $row['day'],
                timeSlot:        $row['slot'],
                scheduleVersion: $version,
            );
        }
        $this->scheduleRepo->replaceAll($entries, $version);

        return $this->scheduleRepo->findByVersion($version);
    }

    public function getIterationCount(): int
    {
        return $this->iterationCount;
    }

    // ─────────────────────────────────────────────────────────────────
    //  BACKTRACKING
    // ─────────────────────────────────────────────────────────────────

    /**
     * @param  array[] $assignments  Atanacak (course, hour_index) listesi
     * @param  int     $index        Şu anki pozisyon
     * @return array[]|null          Çözüm (row dizileri) veya null
     */
    private function backtrack(array $assignments, int $index): ?array
    {
        // Zaman aşımı
        if (microtime(true) - $this->startTime > AppConfig::GENERATION_TIMEOUT) {
            return null;
        }
        // İterasyon limiti
        if (++$this->iterationCount > AppConfig::BACKTRACK_LIMIT) {
            return null;
        }
        // Temel durum: hepsi atandı
        if ($index === count($assignments)) {
            return $this->partial;
        }

        $assignment = $assignments[$index];
        /** @var Course $course */
        $course = $assignment['course'];
        $rooms  = $this->roomRepo->findByMinCapacity($course->requiredCapacity);

        foreach (AppConfig::DAYS as $day) {
            foreach (array_keys(AppConfig::TIME_SLOTS) as $slot) {
                // Hoca müsaitlik kısıtı
                if (!$this->availRepo->isTeacherAvailable($course->teacherId, $day, $slot)) {
                    continue;
                }

                foreach ($rooms as $room) {
                    if ($this->isAssignmentValid($course, $room, $day, $slot)) {
                        // Ata
                        $row = [
                            'course_id' => $course->id,
                            'room_id'   => $room->id,
                            'day'       => $day,
                            'slot'      => $slot,
                            'teacher_id'=> $course->teacherId,
                            'group_id'  => $course->studentGroupId,
                        ];
                        $this->partial[] = $row;

                        $result = $this->backtrack($assignments, $index + 1);
                        if ($result !== null) {
                            return $result;
                        }

                        // Geri al
                        array_pop($this->partial);
                    }
                }
            }
        }

        return null; // Bu dalda çözüm yok
    }

    // ─────────────────────────────────────────────────────────────────
    //  KISIT KONTROLÜ
    // ─────────────────────────────────────────────────────────────────

    private function isAssignmentValid(
        Course $course,
        Room   $room,
        string $day,
        int    $slot
    ): bool {
        foreach ($this->partial as $existing) {
            if ($existing['day'] !== $day || $existing['slot'] !== $slot) {
                continue;
            }
            // Hoca çakışması
            if ($existing['teacher_id'] === $course->teacherId) return false;
            // Oda çakışması
            if ($existing['room_id']    === $room->id)           return false;
            // Grup çakışması
            if ($existing['group_id']   === $course->studentGroupId) return false;
        }

        // Aynı ders, aynı gün'de birden fazla kez atanmasın
        $sameCourseSameDay = array_filter(
            $this->partial,
            fn($r) => $r['course_id'] === $course->id && $r['day'] === $day
        );
        if (count($sameCourseSameDay) >= 1) return false;

        return true;
    }

    // ─────────────────────────────────────────────────────────────────
    //  YARDIMCILAR
    // ─────────────────────────────────────────────────────────────────

    /**
     * Her dersin weekly_hours kadar atama ihtiyacını listeler.
     * weekly_hours=2 → aynı ders 2 slot ister.
     */
    private function buildAssignmentList(): array
    {
        $list    = [];
        $courses = $this->courseRepo->findAll();

        foreach ($courses as $course) {
            for ($h = 0; $h < $course->weeklyHours; $h++) {
                $list[] = ['course' => $course, 'hour_index' => $h];
            }
        }

        // Önce çok kısıtlıları (az uygun oda seçeneği olanları) sırala → verimliliği artırır
        usort($list, function ($a, $b) {
            $roomsA = count($this->roomRepo->findByMinCapacity($a['course']->requiredCapacity));
            $roomsB = count($this->roomRepo->findByMinCapacity($b['course']->requiredCapacity));
            return $roomsA <=> $roomsB; // az seçenekli önce
        });

        return $list;
    }

    /**
     * Çözüm bulunamadığında olası nedenleri analiz eder.
     * @return string[]
     */
    private function diagnoseFailure(array $assignments): array
    {
        $reasons = [];
        $courses  = $this->courseRepo->findAll();
        $rooms    = $this->roomRepo->findAll();
        $totalSlots = count(AppConfig::DAYS) * count(AppConfig::TIME_SLOTS);

        // Toplam slot sayısı yeterliliği
        $totalHours = array_sum(array_map(fn($c) => $c->weeklyHours, $courses));
        if ($totalHours > $totalSlots) {
            $reasons[] = "Toplam ders saati ({$totalHours}) mevcut slot sayısını ({$totalSlots}) aşıyor.";
        }

        // Oda kapasitesi
        foreach ($courses as $course) {
            $suitable = $this->roomRepo->findByMinCapacity($course->requiredCapacity);
            if (empty($suitable)) {
                $reasons[] = "'{$course->name}' dersi için yeterli kapasiteli oda yok "
                           . "(gerekli: {$course->requiredCapacity}).";
            }
        }

        // Hoca aşırı yükü
        $teacherHours = [];
        foreach ($courses as $course) {
            $teacherHours[$course->teacherId] = ($teacherHours[$course->teacherId] ?? 0)
                                              + $course->weeklyHours;
        }
        foreach ($teacherHours as $tid => $hours) {
            if ($hours > $totalSlots) {
                $reasons[] = "Hoca #{$tid}'nin toplam ders yükü ({$hours} saat) slot sayısını aşıyor.";
            }
        }

        if (empty($reasons)) {
            $reasons[] = 'Kısıtlar çok sıkı: backtracking çözüm bulamadı. '
                       . 'Müsaitlik veya oda kısıtlarını gevşetin.';
        }

        return $reasons;
    }
}
