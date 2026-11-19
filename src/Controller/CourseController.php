<?php
declare(strict_types=1);
namespace App\Controller;

use App\Service\CourseService;

class CourseController extends BaseController
{
    public function __construct(
        private readonly CourseService $service,
    ) {}

    public function index(): void
    {
        $courses = $this->service->getAll();
        $this->jsonSuccess(array_map(fn($c) => $c->toArray(), $courses));
    }

    public function show(int $id): void
    {
        $course = $this->service->getById($id);
        $course
            ? $this->jsonSuccess($course->toArray())
            : $this->jsonError("Ders bulunamadı: #{$id}", 404);
    }

    public function store(): void
    {
        if (!$this->requireMethod('POST')) return;
        try {
            $course = $this->service->create($this->getBody());
            $this->jsonSuccess($course->toArray(), 'Ders eklendi.');
        } catch (\InvalidArgumentException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function update(int $id): void
    {
        if (!$this->requireMethod('PUT')) return;
        try {
            $course = $this->service->update($id, $this->getBody());
            $this->jsonSuccess($course->toArray(), 'Ders güncellendi.');
        } catch (\InvalidArgumentException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function destroy(int $id): void
    {
        if (!$this->requireMethod('DELETE')) return;
        $this->service->delete($id)
            ? $this->jsonSuccess(message: 'Ders silindi.')
            : $this->jsonError("Ders bulunamadı: #{$id}", 404);
    }
}
