<?php
declare(strict_types=1);
namespace App\Controller;

use App\Entity\Room;
use App\Repository\RoomRepository;

class RoomController extends BaseController
{
    public function __construct(private readonly RoomRepository $repo) {}

    public function index(): void
    {
        $this->jsonSuccess(array_map(fn($r) => $r->toArray(), $this->repo->findAll()));
    }

    public function show(int $id): void
    {
        $room = $this->repo->findById($id);
        $room ? $this->jsonSuccess($room->toArray()) : $this->jsonError("Derslik bulunamadı: #{$id}", 404);
    }

    public function store(): void
    {
        if (!$this->requireMethod('POST')) return;
        $data = $this->getBody();
        if (empty($data['name']))     { $this->jsonError('Derslik adı boş olamaz.'); return; }
        if (empty($data['capacity'])) { $this->jsonError('Kapasite boş olamaz.'); return; }
        $room = new Room(0, $data['name'], (int)$data['capacity'],
                         $data['building'] ?? '', $data['room_type'] ?? 'classroom');
        $this->jsonSuccess($this->repo->save($room)->toArray(), 'Derslik eklendi.');
    }

    public function update(int $id): void
    {
        if (!$this->requireMethod('PUT')) return;
        if (!$this->repo->findById($id)) { $this->jsonError("Derslik bulunamadı: #{$id}", 404); return; }
        $data = $this->getBody();
        $room = new Room($id, $data['name'] ?? '', (int)($data['capacity'] ?? 30),
                         $data['building'] ?? '', $data['room_type'] ?? 'classroom');
        $this->jsonSuccess($this->repo->save($room)->toArray(), 'Derslik güncellendi.');
    }

    public function destroy(int $id): void
    {
        if (!$this->requireMethod('DELETE')) return;
        $this->repo->delete($id)
            ? $this->jsonSuccess(message: 'Derslik silindi.')
            : $this->jsonError("Derslik bulunamadı: #{$id}", 404);
    }
}
