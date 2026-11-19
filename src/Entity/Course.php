<?php
declare(strict_types=1);
namespace App\Entity;

class Course
{
    public function __construct(
        public int    $id,
        public string $code,
        public string $name,
        public int    $teacherId,
        public int    $studentGroupId,
        public int    $weeklyHours     = 2,
        public int    $requiredCapacity = 25,
        public string $courseType      = 'theory',  // theory | lab | seminar
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:               (int)$data['id'],
            code:             $data['code'],
            name:             $data['name'],
            teacherId:        (int)$data['teacher_id'],
            studentGroupId:   (int)$data['student_group_id'],
            weeklyHours:      (int)($data['weekly_hours']      ?? 2),
            requiredCapacity: (int)($data['required_capacity'] ?? 25),
            courseType:       $data['course_type'] ?? 'theory',
        );
    }

    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'code'             => $this->code,
            'name'             => $this->name,
            'teacher_id'       => $this->teacherId,
            'student_group_id' => $this->studentGroupId,
            'weekly_hours'     => $this->weeklyHours,
            'required_capacity'=> $this->requiredCapacity,
            'course_type'      => $this->courseType,
        ];
    }
}
