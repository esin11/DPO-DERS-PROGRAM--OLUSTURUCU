<?php
declare(strict_types=1);
namespace App\Controller;

use App\Exception\NoSolutionException;
use App\Service\ConflictCheckerService;
use App\Service\SchedulerService;
use App\Service\ScheduleViewService;
use App\Repository\CourseRepository;
use App\Repository\ScheduleRepository;

class ScheduleController extends BaseController
{
    public function __construct(
        private readonly SchedulerService      $scheduler,
        private readonly ScheduleViewService   $viewService,
        private readonly ConflictCheckerService $conflictChecker,
        private readonly CourseRepository       $courseRepo,
        private readonly ScheduleRepository     $scheduleRepo,
    ) {}

    /** GET /api/schedule?version=N  — haftalık grid */
    public function index(): void
    {
        $version = (int)($_GET['version'] ?? $this->viewService->getLatestVersion());
        $grid    = $this->viewService->getWeeklyGrid(max(1, $version));
        $this->jsonSuccess([
            'version' => $version,
            'grid'    => $this->serializeGrid($grid),
        ]);
    }

    /** POST /api/schedule/generate  — otomatik program üret */
    public function generate(): void
    {
        if (!$this->requireMethod('POST')) return;
        try {
            $start   = microtime(true);
            $entries = $this->scheduler->generate();
            $elapsed = round(microtime(true) - $start, 2);
            $this->jsonSuccess([
                'count'      => count($entries),
                'iterations' => $this->scheduler->getIterationCount(),
                'elapsed_s'  => $elapsed,
            ], "Program başarıyla oluşturuldu ({$elapsed}s).");
        } catch (NoSolutionException $e) {
            $this->jsonError($e->getMessage(), 422, ['reasons' => $e->getReasons()]);
        } catch (\Throwable $e) {
            $this->jsonError('Beklenmeyen hata: ' . $e->getMessage(), 500);
        }
    }

    /** GET /api/schedule/conflicts?version=N */
    public function conflicts(): void
    {
        $version   = (int)($_GET['version'] ?? $this->viewService->getLatestVersion());
        $conflicts = $this->conflictChecker->findAllConflicts(max(1, $version));
        $this->jsonSuccess([
            'version'   => $version,
            'count'     => count($conflicts),
            'conflicts' => array_map(fn($c) => $c->toArray(), $conflicts),
        ]);
    }

    /** POST /api/schedule/check  — anlık çakışma kontrolü (AJAX) */
    public function check(): void
    {
        if (!$this->requireMethod('POST')) return;
        $body = $this->getBody();

        $courseId = (int)($body['course_id'] ?? 0);
        $roomId   = (int)($body['room_id']   ?? 0);
        $day      = $body['day']  ?? '';
        $slot     = (int)($body['slot']      ?? 0);
        $version  = (int)($body['version']   ?? $this->viewService->getLatestVersion());

        if (!$courseId || !$roomId || !$day || !$slot) {
            $this->jsonError('Eksik parametre: course_id, room_id, day, slot gerekli.');
            return;
        }

        $course = $this->courseRepo->findById($courseId);
        if (!$course) {
            $this->jsonError("Ders bulunamadı: #{$courseId}", 404);
            return;
        }

        $existing  = $this->scheduleRepo->findByVersion($version);
        $conflicts = $this->conflictChecker->checkSingleAssignment(
            $course, $roomId, $day, $slot, $existing
        );

        $this->jsonSuccess([
            'has_conflict' => !empty($conflicts),
            'conflicts'    => array_map(fn($c) => $c->toArray(), $conflicts),
        ]);
    }

    /** PUT /api/schedule/entry/{id}  — manuel düzenleme */
    public function updateEntry(int $id): void
    {
        if (!$this->requireMethod('PUT')) return;
        $body    = $this->getBody();
        $entry   = $this->scheduleRepo->findById($id);
        if (!$entry) {
            $this->jsonError("Kayıt bulunamadı: #{$id}", 404);
            return;
        }

        $newDay  = $body['day']  ?? $entry->dayOfWeek;
        $newSlot = (int)($body['slot'] ?? $entry->timeSlot);
        $newRoom = (int)($body['room_id'] ?? $entry->roomId);

        $course    = $this->courseRepo->findById($entry->courseId);
        $existing  = array_filter(
            $this->scheduleRepo->findByVersion($entry->scheduleVersion),
            fn($e) => $e->id !== $id
        );
        $conflicts = $this->conflictChecker->checkSingleAssignment(
            $course, $newRoom, $newDay, $newSlot, array_values($existing)
        );

        if (!empty($conflicts)) {
            $this->jsonError('Çakışma tespit edildi.', 409, [
                'conflicts' => array_map(fn($c) => $c->toArray(), $conflicts),
            ]);
            return;
        }

        $entry->dayOfWeek = $newDay;
        $entry->timeSlot  = $newSlot;
        $entry->roomId    = $newRoom;
        $saved = $this->scheduleRepo->save($entry);
        $this->jsonSuccess($saved->toArray(), 'Kayıt güncellendi.');
    }

    /** GET /api/schedule/view?by=teacher&id=1&version=1 */
    public function view(): void
    {
        $by      = $_GET['by']      ?? 'all';
        $id      = (int)($_GET['id'] ?? 0);
        $version = (int)($_GET['version'] ?? $this->viewService->getLatestVersion());

        $dtos = match($by) {
            'teacher' => $this->viewService->getByTeacher($id, $version),
            'group'   => $this->viewService->getByGroup($id, $version),
            'room'    => $this->viewService->getByRoom($id, $version),
            default   => $this->viewService->getAllDTOs($version),
        };

        $this->jsonSuccess(array_map(fn($d) => $d->toArray(), $dtos));
    }

    // ─────────────────────────────────────────────────────────────────

    private function serializeGrid(array $grid): array
    {
        $out = [];
        foreach ($grid as $day => $slots) {
            foreach ($slots as $slot => $dtos) {
                $out[$day][$slot] = array_map(fn($d) => $d->toArray(), $dtos);
            }
        }
        return $out;
    }
}
