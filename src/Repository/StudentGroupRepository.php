<?php
declare(strict_types=1);
namespace App\Repository;

use App\Entity\StudentGroup;

class StudentGroupRepository extends AbstractJsonRepository
{
    public function __construct()
    {
        parent::__construct('student_groups.json');
    }

    public function findById(int $id): ?StudentGroup
    {
        $row = parent::findById($id);
        return $row ? StudentGroup::fromArray($row) : null;
    }

    /** @return StudentGroup[] */
    public function findAll(): array
    {
        return array_map(
            fn(array $row) => StudentGroup::fromArray($row),
            parent::findAll()
        );
    }

    public function save(StudentGroup $group): StudentGroup
    {
        $saved = parent::save($group->toArray());
        return StudentGroup::fromArray($saved);
    }

    public function delete(int $id): bool
    {
        return parent::delete($id);
    }

    public function findByGrade(int $grade): array
    {
        return array_values(array_filter(
            $this->findAll(),
            fn(StudentGroup $g) => $g->grade === $grade
        ));
    }
}
