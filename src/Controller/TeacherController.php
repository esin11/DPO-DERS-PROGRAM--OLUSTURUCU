<?php
declare(strict_types=1);
namespace App\Controller;

use App\Service\TeacherService;

class TeacherController extends BaseController
{
    public function __construct(
        private readonly TeacherService $service,
    ) {}

    public function index(): void
    {
        $teachers = $this->service->getAll();
        $this->jsonSuccess(array_map(fn($t) => $t->toArray(), $teachers));
    }

    public function show(int $id): void
    {
        $teacher = $this->service->getById($id);
        $teacher
            ? $this->jsonSuccess($teacher->toArray())
            : $this->jsonError("Hoca bulunamadı: #{$id}", 404);
    }

    public function store(): void
    {
        if (!$this->requireMethod('POST')) return;
        try {
            $teacher = $this->service->create($this->getBody());
            $this->jsonSuccess($teacher->toArray(), 'Hoca eklendi.');
        } catch (\InvalidArgumentException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function update(int $id): void
    {
        if (!$this->requireMethod('PUT')) return;
        try {
            $teacher = $this->service->update($id, $this->getBody());
            $this->jsonSuccess($teacher->toArray(), 'Hoca güncellendi.');
        } catch (\InvalidArgumentException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function destroy(int $id): void
    {
        if (!$this->requireMethod('DELETE')) return;
        $this->service->delete($id)
            ? $this->jsonSuccess(message: 'Hoca silindi.')
            : $this->jsonError("Hoca bulunamadı: #{$id}", 404);
    }
}
