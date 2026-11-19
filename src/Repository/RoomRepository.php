<?php
declare(strict_types=1);
namespace App\Repository;

use App\Entity\Room;

class RoomRepository extends AbstractJsonRepository
{
    public function __construct()
    {
        parent::__construct('rooms.json');
    }

    public function findById(int $id): ?Room
    {
        $row = parent::findById($id);
        return $row ? Room::fromArray($row) : null;
    }

    /** @return Room[] */
    public function findAll(): array
    {
        return array_map(
            fn(array $row) => Room::fromArray($row),
            parent::findAll()
        );
    }

    public function save(Room $room): Room
    {
        $saved = parent::save($room->toArray());
        return Room::fromArray($saved);
    }

    public function delete(int $id): bool
    {
        return parent::delete($id);
    }

    /** Minimum kapasiteye sahip odaları filtrele */
    public function findByMinCapacity(int $minCapacity): array
    {
        return array_values(array_filter(
            $this->findAll(),
            fn(Room $r) => $r->capacity >= $minCapacity
        ));
    }
}
