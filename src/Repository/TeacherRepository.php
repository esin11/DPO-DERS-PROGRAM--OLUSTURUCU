<?php
declare(strict_types=1);
namespace App\Repository;

use App\Entity\Teacher;

class TeacherRepository extends AbstractJsonRepository
{
    public function __construct()
    {
        parent::__construct('teachers.json');
    }

    public function findById(int $id): ?Teacher
    {
        $row = parent::findById($id);
        return $row ? Teacher::fromArray($row) : null;
    }

    /** @return Teacher[] */
    public function findAll(): array
    {
        return array_map(
            fn(array $row) => Teacher::fromArray($row),
            parent::findAll()
        );
    }

    public function save(Teacher $teacher): Teacher
    {
        $saved = parent::save($teacher->toArray());
        return Teacher::fromArray($saved);
    }

    public function delete(int $id): bool
    {
        return parent::delete($id);
    }

    public function findByDepartment(string $department): array
    {
        return array_values(array_filter(
            $this->findAll(),
            fn(Teacher $t) => strcasecmp($t->department, $department) === 0
        ));
    }
}
