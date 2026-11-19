<?php
declare(strict_types=1);
namespace App\Entity;

class Teacher
{
    public function __construct(
        public int    $id,
        public string $name,
        public string $email,
        public string $department,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:         (int)$data['id'],
            name:       $data['name'],
            email:      $data['email'],
            department: $data['department'],
        );
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'department' => $this->department,
        ];
    }
}
