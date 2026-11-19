<?php
declare(strict_types=1);
namespace App\Entity;

class Room
{
    public function __construct(
        public int    $id,
        public string $name,
        public int    $capacity,
        public string $building  = '',
        public string $roomType  = 'classroom',   // classroom | lab | auditorium
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:       (int)$data['id'],
            name:     $data['name'],
            capacity: (int)$data['capacity'],
            building: $data['building']  ?? '',
            roomType: $data['room_type'] ?? 'classroom',
        );
    }

    public function toArray(): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'capacity'  => $this->capacity,
            'building'  => $this->building,
            'room_type' => $this->roomType,
        ];
    }
}
