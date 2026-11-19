<?php
declare(strict_types=1);
namespace App\Controller;

use App\Entity\StudentGroup;
use App\Repository\StudentGroupRepository;

class StudentGroupController extends BaseController
{
    public function __construct(private readonly StudentGroupRepository $repo) {}

    public function index(): void
    {
        $this->jsonSuccess(array_map(fn($g) => $g->toArray(), $this->repo->findAll()));
    }

    public function show(int $id): void
    {
        $group = $this->repo->findById($id);
        $group ? $this->jsonSuccess($group->toArray()) : $this->jsonError("Grup bulunamadı: #{$id}", 404);
    }

    public function store(): void
    {
        if (!$this->requireMethod('POST')) return;
        $data = $this->getBody();
        if (empty($data['name']))          { $this->jsonError('Grup adı boş olamaz.'); return; }
        if (empty($data['student_count'])) { $this->jsonError('Öğrenci sayısı boş olamaz.'); return; }
        $group = new StudentGroup(0, $data['name'], (int)($data['grade'] ?? 1),
                                  (int)$data['student_count'], $data['department'] ?? '');
        $this->jsonSuccess($this->repo->save($group)->toArray(), 'Grup eklendi.');
    }

    public function update(int $id): void
    {
        if (!$this->requireMethod('PUT')) return;
        if (!$this->repo->findById($id)) { $this->jsonError("Grup bulunamadı: #{$id}", 404); return; }
        $data  = $this->getBody();
        $group = new StudentGroup($id, $data['name'] ?? '', (int)($data['grade'] ?? 1),
                                  (int)($data['student_count'] ?? 25), $data['department'] ?? '');
        $this->jsonSuccess($this->repo->save($group)->toArray(), 'Grup güncellendi.');
    }

    public function destroy(int $id): void
    {
        if (!$this->requireMethod('DELETE')) return;
        $this->repo->delete($id)
            ? $this->jsonSuccess(message: 'Grup silindi.')
            : $this->jsonError("Grup bulunamadı: #{$id}", 404);
    }
}
