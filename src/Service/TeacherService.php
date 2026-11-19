<?php
declare(strict_types=1);
namespace App\Service;

use App\Entity\Teacher;
use App\Repository\TeacherRepository;

class TeacherService
{
    public function __construct(
        private readonly TeacherRepository $repo,
    ) {}

    /** @return Teacher[] */
    public function getAll(): array
    {
        return $this->repo->findAll();
    }

    public function getById(int $id): ?Teacher
    {
        return $this->repo->findById($id);
    }

    public function create(array $data): Teacher
    {
        $this->validate($data);
        $teacher = new Teacher(
            id:         0,
            name:       trim($data['name']),
            email:      trim($data['email']),
            department: trim($data['department']),
        );
        return $this->repo->save($teacher);
    }

    public function update(int $id, array $data): Teacher
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \InvalidArgumentException("Hoca bulunamadı: #{$id}");
        }
        $this->validate($data);
        $teacher = new Teacher(
            id:         $id,
            name:       trim($data['name']),
            email:      trim($data['email']),
            department: trim($data['department']),
        );
        return $this->repo->save($teacher);
    }

    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
    }

    private function validate(array $data): void
    {
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Hoca adı boş olamaz.');
        }
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Geçerli bir e-posta adresi giriniz.');
        }
        if (empty($data['department'])) {
            throw new \InvalidArgumentException('Bölüm adı boş olamaz.');
        }
    }
}
