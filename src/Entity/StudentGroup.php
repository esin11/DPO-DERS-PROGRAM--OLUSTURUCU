<?php
declare(strict_types=1);
namespace App\Entity;

class StudentGroup
{
    public function __construct(
        public int    $id,
        public string $name,
        public int    $grade,
        public int    $studentCount,
        public string $department = '',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:           (int)$data['id'],
            name:         $data['name'],
            grade:        (int)$data['grade'],
            studentCount: (int)$data['student_count'],
            department:   $data['department'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'grade'         => $this->grade,
            'student_count' => $this->studentCount,
            'department'    => $this->department,
        ];
    }
}
