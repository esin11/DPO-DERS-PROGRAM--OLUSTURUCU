<?php
declare(strict_types=1);

use App\Config\AppConfig;
use App\Controller\CourseController;
use App\Controller\RoomController;
use App\Controller\ScheduleController;
use App\Controller\StudentGroupController;
use App\Controller\TeacherController;
use App\Repository\AvailabilityRepository;
use App\Repository\CourseRepository;
use App\Repository\RoomRepository;
use App\Repository\ScheduleRepository;
use App\Repository\StudentGroupRepository;
use App\Repository\TeacherRepository;
use App\Service\ConflictCheckerService;
use App\Service\CourseService;
use App\Service\ScheduleViewService;
use App\Service\SchedulerService;
use App\Service\TeacherService;

require_once __DIR__ . '/../vendor/autoload.php';

// ─── ENV ──────────────────────────────────────────────────────────────────────
if (file_exists(__DIR__ . '/../.env')) {
    foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        $_ENV[trim($k)] = trim($v);
    }
}

// ─── CORS / Headers ───────────────────────────────────────────────────────────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ─── Hata yönetimi ────────────────────────────────────────────────────────────
set_exception_handler(function (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
});

// ─── DI (manuel, basit) ───────────────────────────────────────────────────────
$teacherRepo  = new TeacherRepository();
$roomRepo     = new RoomRepository();
$groupRepo    = new StudentGroupRepository();
$courseRepo   = new CourseRepository();
$availRepo    = new AvailabilityRepository();
$scheduleRepo = new ScheduleRepository();

$conflictChecker = new ConflictCheckerService($courseRepo, $roomRepo, $availRepo, $scheduleRepo);
$scheduler       = new SchedulerService($courseRepo, $roomRepo, $availRepo, $scheduleRepo, $conflictChecker);
$viewService     = new ScheduleViewService($scheduleRepo, $courseRepo, $teacherRepo, $roomRepo, $groupRepo);

$teacherCtrl  = new TeacherController(new TeacherService($teacherRepo));
$courseCtrl   = new CourseController(new CourseService($courseRepo, $teacherRepo, $groupRepo));
$scheduleCtrl = new ScheduleController($scheduler, $viewService, $conflictChecker, $courseRepo, $scheduleRepo);
$roomCtrl     = new RoomController($roomRepo);
$groupCtrl    = new StudentGroupController($groupRepo);

// ─── Routing ──────────────────────────────────────────────────────────────────
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri    = rtrim($uri, '/');
$method = $_SERVER['REQUEST_METHOD'];

$routes = [
    // Teachers
    ['GET',    '#^/api/teachers$#',           fn() => $teacherCtrl->index()],
    ['GET',    '#^/api/teachers/(\d+)$#',     fn($id) => $teacherCtrl->show((int)$id)],
    ['POST',   '#^/api/teachers$#',           fn() => $teacherCtrl->store()],
    ['PUT',    '#^/api/teachers/(\d+)$#',     fn($id) => $teacherCtrl->update((int)$id)],
    ['DELETE', '#^/api/teachers/(\d+)$#',     fn($id) => $teacherCtrl->destroy((int)$id)],

    // Courses
    ['GET',    '#^/api/courses$#',            fn() => $courseCtrl->index()],
    ['GET',    '#^/api/courses/(\d+)$#',      fn($id) => $courseCtrl->show((int)$id)],
    ['POST',   '#^/api/courses$#',            fn() => $courseCtrl->store()],
    ['PUT',    '#^/api/courses/(\d+)$#',      fn($id) => $courseCtrl->update((int)$id)],
    ['DELETE', '#^/api/courses/(\d+)$#',      fn($id) => $courseCtrl->destroy((int)$id)],

    // Rooms
    ['GET',    '#^/api/rooms$#',              fn() => $roomCtrl->index()],
    ['GET',    '#^/api/rooms/(\d+)$#',        fn($id) => $roomCtrl->show((int)$id)],
    ['POST',   '#^/api/rooms$#',              fn() => $roomCtrl->store()],
    ['PUT',    '#^/api/rooms/(\d+)$#',        fn($id) => $roomCtrl->update((int)$id)],
    ['DELETE', '#^/api/rooms/(\d+)$#',        fn($id) => $roomCtrl->destroy((int)$id)],

    // Student Groups
    ['GET',    '#^/api/groups$#',             fn() => $groupCtrl->index()],
    ['GET',    '#^/api/groups/(\d+)$#',       fn($id) => $groupCtrl->show((int)$id)],
    ['POST',   '#^/api/groups$#',             fn() => $groupCtrl->store()],
    ['PUT',    '#^/api/groups/(\d+)$#',       fn($id) => $groupCtrl->update((int)$id)],
    ['DELETE', '#^/api/groups/(\d+)$#',       fn($id) => $groupCtrl->destroy((int)$id)],

    // Schedule
    ['GET',    '#^/api/schedule$#',                fn() => $scheduleCtrl->index()],
    ['POST',   '#^/api/schedule/generate$#',       fn() => $scheduleCtrl->generate()],
    ['GET',    '#^/api/schedule/conflicts$#',      fn() => $scheduleCtrl->conflicts()],
    ['POST',   '#^/api/schedule/check$#',          fn() => $scheduleCtrl->check()],
    ['GET',    '#^/api/schedule/view$#',           fn() => $scheduleCtrl->view()],
    ['PUT',    '#^/api/schedule/entry/(\d+)$#',    fn($id) => $scheduleCtrl->updateEntry((int)$id)],

    // Ana sayfa → UI
    ['GET',    '#^/?$#',                      function() {
        include __DIR__ . '/index.html';
    }],
];

foreach ($routes as [$routeMethod, $pattern, $handler]) {
    if ($routeMethod !== $method && $routeMethod !== 'GET') continue;
    if (($routeMethod !== $method)) continue;
    if (preg_match($pattern, $uri, $matches)) {
        array_shift($matches);
        $handler(...$matches);
        exit;
    }
}

http_response_code(404);
echo json_encode(['success' => false, 'message' => 'Endpoint bulunamadı.'], JSON_UNESCAPED_UNICODE);
