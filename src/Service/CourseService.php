<?php
declare(strict_types=1);
namespace App\Service;

use App\Entity\Course;
use App\Repository\CourseRepository;
use App\Repository\TeacherRepository;
use App\Repository\StudentGroupRepository;

class CourseService
{
    public function __construct(
        private readonly CourseRepository       $courseRepo,
        private readonly TeacherRepository      $teacherRepo,
        private readonly StudentGroupRepository $groupRepo,
    ) {}

    /** @return Course[] */
    public function getAll(): array
    {
        return $this->courseRepo->findAll();
    }

    public function getById(int $id): ?Course
    {
        return $this->courseRepo->findById($id);
    }

    public function create(array $data): Course
    {
        $this->validate($data);
        $course = new Course(
            id:               0,
            code:             trim($data['code']),
            name:             trim($data['name']),
            teacherId:        (int)$data['teacher_id'],
            studentGroupId:   (int)$data['student_group_id'],
            weeklyHours:      (int)($data['weekly_hours']       ?? 2),
            requiredCapacity: (int)($data['required_capacity']  ?? 25),
            courseType:       $data['course_type'] ?? 'theory',
        );
        return $this->courseRepo->save($course);
    }

    public function update(int $id, array $data): Course
    {
        if (!$this->courseRepo->findById($id)) {
            throw new \InvalidArgumentException("Ders bulunamadı: #{$id}");
        }
        $this->validate($data);
        $course = new Course(
            id:               $id,
            code:             trim($data['code']),
            name:             trim($data['name']),
            teacherId:        (int)$data['teacher_id'],
            studentGroupId:   (int)$data['student_group_id'],
            weeklyHours:      (int)($data['weekly_hours']       ?? 2),
            requiredCapacity: (int)($data['required_capacity']  ?? 25),
            courseType:       $data['course_type'] ?? 'theory',
        );
        return $this->courseRepo->save($course);
    }

    public function delete(int $id): bool
    {
        return $this->courseRepo->delete($id);
    }

    private function validate(array $data): void
    {
        if (empty($data['code'])) throw new \InvalidArgumentException('Ders kodu boş olamaz.');
        if (empty($data['name'])) throw new \InvalidArgumentException('Ders adı boş olamaz.');
        if (empty($data['teacher_id'])) throw new \InvalidArgumentException('Öğretmen seçiniz.');
        if (empty($data['student_group_id'])) throw new \InvalidArgumentException('Öğrenci grubu seçiniz.');

        $hours = (int)($data['weekly_hours'] ?? 0);
        if ($hours < 1 || $hours > 9) {
            throw new \InvalidArgumentException('Haftalık saat 1-9 arasında olmalı.');
        }

        if (!$this->teacherRepo->findById((int)$data['teacher_id'])) {
            throw new \InvalidArgumentException("Öğretmen bulunamadı: #{$data['teacher_id']}");
        }
        if (!$this->groupRepo->findById((int)$data['student_group_id'])) {
            throw new \InvalidArgumentException("Grup bulunamadı: #{$data['student_group_id']}");
        }
    }
}
