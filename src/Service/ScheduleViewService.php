<?php
declare(strict_types=1);
namespace App\Service;

use App\Config\AppConfig;
use App\DTO\ScheduleEntryDTO;
use App\Entity\ScheduleEntry;
use App\Repository\CourseRepository;
use App\Repository\RoomRepository;
use App\Repository\ScheduleRepository;
use App\Repository\StudentGroupRepository;
use App\Repository\TeacherRepository;

/**
 * Program görünüm servisi.
 * Ham ScheduleEntry'leri UI için DTO'ya dönüştürür.
 * Hoca, grup, derslik bazlı filtreleme sağlar.
 */
class ScheduleViewService
{
    public function __construct(
        private readonly ScheduleRepository     $scheduleRepo,
        private readonly CourseRepository       $courseRepo,
        private readonly TeacherRepository      $teacherRepo,
        private readonly RoomRepository         $roomRepo,
        private readonly StudentGroupRepository $groupRepo,
    ) {}

    /**
     * Haftalık tablo için tam program → [gün][slot] = DTO[]
     * @return array<string, array<int, ScheduleEntryDTO[]>>
     */
    public function getWeeklyGrid(int $version = 1): array
    {
        $grid = [];
        foreach (AppConfig::DAYS as $day) {
            foreach (array_keys(AppConfig::TIME_SLOTS) as $slot) {
                $grid[$day][$slot] = [];
            }
        }

        foreach ($this->scheduleRepo->findByVersion($version) as $entry) {
            $dto = $this->toDTO($entry);
            if ($dto) {
                $grid[$entry->dayOfWeek][$entry->timeSlot][] = $dto;
            }
        }
        return $grid;
    }

    /** Hoca bazlı filtrelenmiş program */
    public function getByTeacher(int $teacherId, int $version = 1): array
    {
        return array_filter(
            $this->getAllDTOs($version),
            fn(ScheduleEntryDTO $d) => $this->teacherNameMatchesId($d->teacherName, $teacherId)
        );
    }

    /** Grup bazlı filtrelenmiş program */
    public function getByGroup(int $groupId, int $version = 1): array
    {
        $group = $this->groupRepo->findById($groupId);
        return array_values(array_filter(
            $this->getAllDTOs($version),
            fn(ScheduleEntryDTO $d) => $d->groupName === ($group?->name ?? '')
        ));
    }

    /** Derslik bazlı filtrelenmiş program */
    public function getByRoom(int $roomId, int $version = 1): array
    {
        $room = $this->roomRepo->findById($roomId);
        return array_values(array_filter(
            $this->getAllDTOs($version),
            fn(ScheduleEntryDTO $d) => $d->roomName === ($room?->name ?? '')
        ));
    }

    /** @return ScheduleEntryDTO[] */
    public function getAllDTOs(int $version = 1): array
    {
        $dtos = [];
        foreach ($this->scheduleRepo->findByVersion($version) as $entry) {
            $dto = $this->toDTO($entry);
            if ($dto) $dtos[] = $dto;
        }
        return $dtos;
    }

    public function getLatestVersion(): int
    {
        return $this->scheduleRepo->getLatestVersion();
    }

    // ─────────────────────────────────────────────────────────────────

    private function toDTO(ScheduleEntry $entry): ?ScheduleEntryDTO
    {
        $course  = $this->courseRepo->findById($entry->courseId);
        $room    = $this->roomRepo->findById($entry->roomId);
        if (!$course || !$room) return null;

        $teacher = $this->teacherRepo->findById($course->teacherId);
        $group   = $this->groupRepo->findById($course->studentGroupId);

        return new ScheduleEntryDTO(
            entryId:     $entry->id,
            courseCode:  $course->code,
            courseName:  $course->name,
            teacherName: $teacher?->name ?? '-',
            roomName:    $room->name,
            groupName:   $group?->name ?? '-',
            dayOfWeek:   $entry->dayOfWeek,
            timeSlot:    $entry->timeSlot,
            timeLabel:   AppConfig::TIME_SLOTS[$entry->timeSlot] ?? '-',
            courseType:  $course->courseType,
        );
    }

    private function teacherNameMatchesId(string $name, int $teacherId): bool
    {
        $teacher = $this->teacherRepo->findById($teacherId);
        return $teacher?->name === $name;
    }
}
