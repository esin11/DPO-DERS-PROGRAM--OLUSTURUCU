<?php
declare(strict_types=1);
namespace App\Entity;

/**
 * Üretilmiş çizelgedeki tek bir satır.
 * Bir ders → oda → gün → slot atamasını temsil eder.
 */
class ScheduleEntry
{
    public function __construct(
        public int    $id,
        public int    $courseId,
        public int    $roomId,
        public string $dayOfWeek,
        public int    $timeSlot,
        public int    $scheduleVersion = 1,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:              (int)$data['id'],
            courseId:        (int)$data['course_id'],
            roomId:          (int)$data['room_id'],
            dayOfWeek:       $data['day_of_week'],
            timeSlot:        (int)$data['time_slot'],
            scheduleVersion: (int)($data['schedule_version'] ?? 1),
        );
    }

    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'course_id'        => $this->courseId,
            'room_id'          => $this->roomId,
            'day_of_week'      => $this->dayOfWeek,
            'time_slot'        => $this->timeSlot,
            'schedule_version' => $this->scheduleVersion,
        ];
    }
}
