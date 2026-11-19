<?php
declare(strict_types=1);
namespace App\Service;

use App\Config\AppConfig;
use App\DTO\ConflictDTO;
use App\Entity\Course;
use App\Entity\ScheduleEntry;
use App\Repository\AvailabilityRepository;
use App\Repository\CourseRepository;
use App\Repository\RoomRepository;
use App\Repository\ScheduleRepository;

/**
 * Çakışma kontrolü servis katmanı.
 *
 * Kontrol edilen kısıtlar:
 *  1. Aynı hoca → aynı gün+slot → iki farklı derse atanmamalı
 *  2. Aynı oda  → aynı gün+slot → iki farklı derse atanmamalı
 *  3. Aynı grup → aynı gün+slot → iki farklı derse atanmamalı
 *  4. Oda kapasitesi dersin gerektirdiği kapasiteyi karşılamalı
 *  5. Hoca o gün+slot'ta müsait olmalı
 */
class ConflictCheckerService
{
    public function __construct(
        private readonly CourseRepository       $courseRepo,
        private readonly RoomRepository         $roomRepo,
        private readonly AvailabilityRepository $availRepo,
        private readonly ScheduleRepository     $scheduleRepo,
    ) {}

    /**
     * Mevcut program üzerindeki tüm çakışmaları döner.
     * @return ConflictDTO[]
     */
    public function findAllConflicts(int $version = 1): array
    {
        $entries   = $this->scheduleRepo->findByVersion($version);
        $conflicts = [];

        foreach ($entries as $i => $a) {
            foreach ($entries as $j => $b) {
                if ($j <= $i) continue; // çift kontrolü önle

                if ($a->dayOfWeek === $b->dayOfWeek && $a->timeSlot === $b->timeSlot) {
                    $courseA = $this->courseRepo->findById($a->courseId);
                    $courseB = $this->courseRepo->findById($b->courseId);
                    if (!$courseA || !$courseB) continue;

                    // Hoca çakışması
                    if ($courseA->teacherId === $courseB->teacherId) {
                        $conflicts[] = new ConflictDTO(
                            type:    'teacher',
                            message: "Hoca çakışması: {$courseA->name} ve {$courseB->name} "
                                   . "({$a->dayOfWeek} {$a->timeSlot}. saat) aynı anda planlandı.",
                            context: ['entry_a' => $a->id, 'entry_b' => $b->id,
                                      'teacher_id' => $courseA->teacherId]
                        );
                    }

                    // Oda çakışması
                    if ($a->roomId === $b->roomId) {
                        $room = $this->roomRepo->findById($a->roomId);
                        $conflicts[] = new ConflictDTO(
                            type:    'room',
                            message: "Derslik çakışması: {$room?->name} odasında "
                                   . "{$courseA->name} ve {$courseB->name} "
                                   . "({$a->dayOfWeek} {$a->timeSlot}. saat) aynı anda.",
                            context: ['entry_a' => $a->id, 'entry_b' => $b->id,
                                      'room_id' => $a->roomId]
                        );
                    }

                    // Grup çakışması
                    if ($courseA->studentGroupId === $courseB->studentGroupId) {
                        $conflicts[] = new ConflictDTO(
                            type:    'group',
                            message: "Grup çakışması: {$courseA->name} ve {$courseB->name} "
                                   . "({$a->dayOfWeek} {$a->timeSlot}. saat) aynı gruba aynı anda.",
                            context: ['entry_a' => $a->id, 'entry_b' => $b->id,
                                      'group_id' => $courseA->studentGroupId]
                        );
                    }
                }
            }

            // Kapasite kontrolü
            $course = $this->courseRepo->findById($a->courseId);
            $room   = $this->roomRepo->findById($a->roomId);
            if ($course && $room && $room->capacity < $course->requiredCapacity) {
                $conflicts[] = new ConflictDTO(
                    type:    'capacity',
                    message: "Kapasite yetersiz: {$course->name} → {$room->name} "
                           . "({$room->capacity} < {$course->requiredCapacity} kişi).",
                    context: ['entry_id' => $a->id, 'room_id' => $a->roomId,
                              'course_id' => $a->courseId]
                );
            }

            // Müsaitlik kontrolü
            if ($course && !$this->availRepo->isTeacherAvailable(
                $course->teacherId, $a->dayOfWeek, $a->timeSlot
            )) {
                $conflicts[] = new ConflictDTO(
                    type:    'teacher',
                    message: "Müsaitlik ihlali: {$course->name} için hoca "
                           . "{$a->dayOfWeek} {$a->timeSlot}. saatte müsait değil.",
                    context: ['entry_id' => $a->id, 'teacher_id' => $course->teacherId]
                );
            }
        }

        return $conflicts;
    }

    /**
     * Tekil atama için anlık çakışma kontrolü (AJAX endpoint).
     * @param  ScheduleEntry[] $existingEntries  Mevcut onaylı atamalar
     * @return ConflictDTO[]
     */
    public function checkSingleAssignment(
        Course         $course,
        int            $roomId,
        string         $day,
        int            $slot,
        array          $existingEntries,
    ): array {
        $conflicts = [];
        $room = $this->roomRepo->findById($roomId);

        // 1. Kapasite
        if ($room && $room->capacity < $course->requiredCapacity) {
            $conflicts[] = new ConflictDTO(
                type:    'capacity',
                message: "{$room->name} odası yetersiz ({$room->capacity}/{$course->requiredCapacity}).",
            );
        }

        // 2. Müsaitlik
        if (!$this->availRepo->isTeacherAvailable($course->teacherId, $day, $slot)) {
            $conflicts[] = new ConflictDTO(
                type:    'teacher',
                message: "Hoca {$day} {$slot}. saatte müsait değil.",
            );
        }

        // 3. Mevcut atamalarla çakışma
        foreach ($existingEntries as $entry) {
            if ($entry->dayOfWeek !== $day || $entry->timeSlot !== $slot) continue;

            $other = $this->courseRepo->findById($entry->courseId);
            if (!$other) continue;

            if ($other->teacherId === $course->teacherId) {
                $conflicts[] = new ConflictDTO(
                    type:    'teacher',
                    message: "Hoca zaten {$day} {$slot}. saatte '{$other->name}' dersinde.",
                );
            }
            if ($entry->roomId === $roomId) {
                $conflicts[] = new ConflictDTO(
                    type:    'room',
                    message: "Derslik zaten {$day} {$slot}. saatte '{$other->name}' dersine atanmış.",
                );
            }
            if ($other->studentGroupId === $course->studentGroupId) {
                $conflicts[] = new ConflictDTO(
                    type:    'group',
                    message: "Grup zaten {$day} {$slot}. saatte '{$other->name}' dersinde.",
                );
            }
        }

        return $conflicts;
    }

    public function hasConflicts(int $version = 1): bool
    {
        return count($this->findAllConflicts($version)) > 0;
    }
}
