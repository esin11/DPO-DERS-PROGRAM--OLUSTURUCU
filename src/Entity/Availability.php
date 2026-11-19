<?php
declare(strict_types=1);
namespace App\Entity;

/**
 * Öğretmenin belirli gün+slot'ta müsait olup olmadığını tutar.
 * is_available = false → o hoca o saatte müsait değil (kısıt).
 */
class Availability
{
    public function __construct(
        public int    $id,
        public int    $teacherId,
        public string $dayOfWeek,
        public int    $timeSlot,
        public bool   $isAvailable = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:          (int)$data['id'],
            teacherId:   (int)$data['teacher_id'],
            dayOfWeek:   $data['day_of_week'],
            timeSlot:    (int)$data['time_slot'],
            isAvailable: (bool)($data['is_available'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'teacher_id'   => $this->teacherId,
            'day_of_week'  => $this->dayOfWeek,
            'time_slot'    => $this->timeSlot,
            'is_available' => $this->isAvailable,
        ];
    }
}
