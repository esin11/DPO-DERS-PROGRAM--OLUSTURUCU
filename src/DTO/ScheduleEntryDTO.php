<?php
declare(strict_types=1);
namespace App\DTO;

/**
 * Ders programı görünümünde tek bir hücreyi temsil eder.
 * Controller → View katmanına veri taşır (Entity'leri UI'ya açmaz).
 */
class ScheduleEntryDTO
{
    public function __construct(
        public readonly int    $entryId,
        public readonly string $courseCode,
        public readonly string $courseName,
        public readonly string $teacherName,
        public readonly string $roomName,
        public readonly string $groupName,
        public readonly string $dayOfWeek,
        public readonly int    $timeSlot,
        public readonly string $timeLabel,   // "08:00"
        public readonly string $courseType,
    ) {}

    public function toArray(): array
    {
        return [
            'entry_id'    => $this->entryId,
            'course_code' => $this->courseCode,
            'course_name' => $this->courseName,
            'teacher'     => $this->teacherName,
            'room'        => $this->roomName,
            'group'       => $this->groupName,
            'day'         => $this->dayOfWeek,
            'slot'        => $this->timeSlot,
            'time'        => $this->timeLabel,
            'type'        => $this->courseType,
        ];
    }
}
