<?php
declare(strict_types=1);
namespace App\Repository;

use App\Entity\Availability;

class AvailabilityRepository extends AbstractJsonRepository
{
    public function __construct()
    {
        parent::__construct('availability.json');
    }

    public function findById(int $id): ?Availability
    {
        $row = parent::findById($id);
        return $row ? Availability::fromArray($row) : null;
    }

    /** @return Availability[] */
    public function findAll(): array
    {
        return array_map(
            fn(array $row) => Availability::fromArray($row),
            parent::findAll()
        );
    }

    public function save(Availability $av): Availability
    {
        // Aynı teacher+day+slot varsa güncelle
        foreach ($this->data as $existing) {
            if (
                (int)$existing['teacher_id']  === $av->teacherId &&
                $existing['day_of_week']       === $av->dayOfWeek &&
                (int)$existing['time_slot']    === $av->timeSlot
            ) {
                $av = new Availability(
                    id:          (int)$existing['id'],
                    teacherId:   $av->teacherId,
                    dayOfWeek:   $av->dayOfWeek,
                    timeSlot:    $av->timeSlot,
                    isAvailable: $av->isAvailable,
                );
                break;
            }
        }
        $saved = parent::save($av->toArray());
        return Availability::fromArray($saved);
    }

    public function delete(int $id): bool
    {
        return parent::delete($id);
    }

    /**
     * Hoca için engellenen (müsait olmayan) gün+slot çiftlerini döner.
     * @return array<string, int[]>  ['Pazartesi' => [1,2], ...]
     */
    public function getBlockedSlots(int $teacherId): array
    {
        $blocked = [];
        foreach ($this->findAll() as $av) {
            if ($av->teacherId === $teacherId && !$av->isAvailable) {
                $blocked[$av->dayOfWeek][] = $av->timeSlot;
            }
        }
        return $blocked;
    }

    /**
     * Hoca belirtilen gün+slot'ta müsait mi?
     */
    public function isTeacherAvailable(int $teacherId, string $day, int $slot): bool
    {
        foreach ($this->findAll() as $av) {
            if (
                $av->teacherId === $teacherId &&
                $av->dayOfWeek === $day &&
                $av->timeSlot  === $slot &&
                !$av->isAvailable
            ) {
                return false;
            }
        }
        return true; // kayıt yoksa müsait kabul et
    }
}
