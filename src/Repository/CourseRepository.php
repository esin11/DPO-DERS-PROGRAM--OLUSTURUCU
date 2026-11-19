<?php
declare(strict_types=1);
namespace App\Repository;

use App\Entity\Course;

class CourseRepository extends AbstractJsonRepository
{
    public function __construct()
    {
        parent::__construct('courses.json');
    }

    public function findById(int $id): ?Course
    {
        $row = parent::findById($id);
        return $row ? Course::fromArray($row) : null;
    }

    /** @return Course[] */
    public function findAll(): array
    {
        return array_map(
            fn(array $row) => Course::fromArray($row),
            parent::findAll()
        );
    }

    public function save(Course $course): Course
    {
        $saved = parent::save($course->toArray());
        return Course::fromArray($saved);
    }

    public function delete(int $id): bool
    {
        return parent::delete($id);
    }

    public function findByTeacher(int $teacherId): array
    {
        return array_values(array_filter(
            $this->findAll(),
            fn(Course $c) => $c->teacherId === $teacherId
        ));
    }

    public function findByStudentGroup(int $groupId): array
    {
        return array_values(array_filter(
            $this->findAll(),
            fn(Course $c) => $c->studentGroupId === $groupId
        ));
    }
}
