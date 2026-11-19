<?php
declare(strict_types=1);
namespace App\Repository;

use App\Entity\ScheduleEntry;

class ScheduleRepository extends AbstractJsonRepository
{
    public function __construct()
    {
        parent::__construct('schedule_entries.json');
    }

    public function findById(int $id): ?ScheduleEntry
    {
        $row = parent::findById($id);
        return $row ? ScheduleEntry::fromArray($row) : null;
    }

    /** @return ScheduleEntry[] */
    public function findAll(): array
    {
        return array_map(
            fn(array $row) => ScheduleEntry::fromArray($row),
            parent::findAll()
        );
    }

    public function save(ScheduleEntry $entry): ScheduleEntry
    {
        $saved = parent::save($entry->toArray());
        return ScheduleEntry::fromArray($saved);
    }

    /** Mevcut tüm programı sil, yeni versiyonu kaydet */
    public function replaceAll(array $entries, int $version = 1): void
    {
        // Sadece bu versiyona ait kayıtları temizle
        $this->data = array_filter(
            $this->data,
            fn(array $r) => (int)$r['schedule_version'] !== $version
        );

        foreach ($entries as $entry) {
            /** @var ScheduleEntry $entry */
            $arr = $entry->toArray();
            $arr['schedule_version'] = $version;
            if (empty($arr['id'])) {
                $arr['id'] = $this->nextId();
            }
            $this->data[$arr['id']] = $arr;
        }
        $this->persist();
    }

    public function delete(int $id): bool
    {
        return parent::delete($id);
    }

    public function findByVersion(int $version): array
    {
        return array_values(array_filter(
            $this->findAll(),
            fn(ScheduleEntry $e) => $e->scheduleVersion === $version
        ));
    }

    public function findByCourse(int $courseId): array
    {
        return array_values(array_filter(
            $this->findAll(),
            fn(ScheduleEntry $e) => $e->courseId === $courseId
        ));
    }

    public function findByRoom(int $roomId): array
    {
        return array_values(array_filter(
            $this->findAll(),
            fn(ScheduleEntry $e) => $e->roomId === $roomId
        ));
    }

    public function getLatestVersion(): int
    {
        if (empty($this->data)) return 0;
        return max(array_column(array_values($this->data), 'schedule_version'));
    }
}
