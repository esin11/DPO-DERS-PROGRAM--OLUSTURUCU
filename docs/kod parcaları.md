# DPO - Aşama 2: JSON Tabanlı Mimari ve Çizelgeleme Motoru

Bu doküman, Ders Programı Oluşturucu (DPO) projesinin Aşama 2 geliştirmeleri kapsamında hazırlanan tüm JSON veri setlerini, Strategy tasarım desenini, ConflictValidator ve Recursive Backtracking içeren `ScheduleSolverService` sınıflarını ve `public/index.php` Front Controller yapısını içermektedir.

---

## 1. Veri Yapısı (`storage/`)

### 1.1 `storage/instructors.json`
```json
[
  {"id": 1, "title": "Doç. Dr.", "firstName": "Berk", "lastName": "Dinçer", "email": "berk@example.com"},
  {"id": 2, "title": "Dr. Öğr. Üyesi", "firstName": "Mustafa", "lastName": "Nehoş", "email": "mustafa@example.com"},
  {"id": 3, "title": "Arş. Gör.", "firstName": "Esin", "lastName": "Göç", "email": "esin@example.com"},
  {"id": 4, "title": "Prof. Dr.", "firstName": "Ahmet", "lastName": "Yılmaz", "email": "ahmet@example.com"},
  {"id": 5, "title": "Dr. Öğr. Üyesi", "firstName": "Ayşe", "lastName": "Kaya", "email": "ayse@example.com"}
]
```

### 1.2 `storage/classrooms.json`
```json
[
  {"id": 1, "name": "Amfi 5-6", "building": "Mühendislik", "type": "Amfi", "capacity": 120},
  {"id": 2, "name": "Derslik 101", "building": "Mühendislik", "type": "Standart", "capacity": 50},
  {"id": 3, "name": "Yazılım Lab 1", "building": "Mühendislik", "type": "Lab", "capacity": 40},
  {"id": 4, "name": "Derslik 102", "building": "Mühendislik", "type": "Standart", "capacity": 60}
]
```

### 1.3 `storage/student_groups.json`
```json
[
  {"id": 1, "department": "Yazılım Müh.", "gradeLevel": 1, "section": "A", "studentCount": 45},
  {"id": 2, "department": "Yazılım Müh.", "gradeLevel": 2, "section": "A", "studentCount": 38},
  {"id": 3, "department": "Yazılım Müh.", "gradeLevel": 3, "section": "A", "studentCount": 35}
]
```

### 1.4 `storage/courses.json`
```json
[
  {"id": 1, "code": "YBM101", "name": "Programlamaya Giriş", "weeklyHours": 4, "roomTypeRequired": "Amfi", "instructorId": 1, "studentGroupId": 1},
  {"id": 2, "code": "YBM103", "name": "Matematik I", "weeklyHours": 3, "roomTypeRequired": "Amfi", "instructorId": 4, "studentGroupId": 1},
  {"id": 3, "code": "YBM105", "name": "Fizik I", "weeklyHours": 3, "roomTypeRequired": "Standart", "instructorId": 5, "studentGroupId": 1},
  {"id": 4, "code": "YBM107", "name": "İngilizce I", "weeklyHours": 2, "roomTypeRequired": "Standart", "instructorId": 5, "studentGroupId": 1},
  {"id": 5, "code": "YBM201", "name": "Veri Yapıları", "weeklyHours": 4, "roomTypeRequired": "Lab", "instructorId": 2, "studentGroupId": 2},
  {"id": 6, "code": "YBM203", "name": "Nesneye Yönelimli Prog.", "weeklyHours": 4, "roomTypeRequired": "Lab", "instructorId": 3, "studentGroupId": 2},
  {"id": 7, "code": "YBM205", "name": "Ayrık Matematik", "weeklyHours": 3, "roomTypeRequired": "Standart", "instructorId": 4, "studentGroupId": 2},
  {"id": 8, "code": "YBM207", "name": "Mantık Devreleri", "weeklyHours": 3, "roomTypeRequired": "Standart", "instructorId": 1, "studentGroupId": 2},
  {"id": 9, "code": "YBM301", "name": "Yazılım Mimarisi", "weeklyHours": 4, "roomTypeRequired": "Amfi", "instructorId": 1, "studentGroupId": 3},
  {"id": 10, "code": "YBM303", "name": "Veritabanı Sistemleri", "weeklyHours": 4, "roomTypeRequired": "Lab", "instructorId": 2, "studentGroupId": 3},
  {"id": 11, "code": "YBM305", "name": "Web Geliştirme", "weeklyHours": 3, "roomTypeRequired": "Lab", "instructorId": 3, "studentGroupId": 3},
  {"id": 12, "code": "YBM307", "name": "İşletim Sistemleri", "weeklyHours": 3, "roomTypeRequired": "Standart", "instructorId": 4, "studentGroupId": 3}
]
```

### 1.5 `storage/availability.json`
```json
[
  {"instructorId": 1, "day": "Pazartesi", "timeslot": "08:30-09:30", "isAvailable": false},
  {"instructorId": 1, "day": "Pazartesi", "timeslot": "09:30-10:30", "isAvailable": false},
  {"instructorId": 2, "day": "Cuma", "timeslot": "15:30-16:30", "isAvailable": false}
]
```

---

## 2. Strategy Desen Sınıfları (`src/Strategy/`)

### 2.1 `src/Strategy/ScheduleConstraintInterface.php`
```php
<?php

declare(strict_types=1);

namespace App\Strategy;

use App\Model\Course;
use App\Model\Classroom;

interface ScheduleConstraintInterface
{
    public function validate(
        Course $course,
        Classroom $classroom,
        string $day,
        string $timeslot,
        array $currentSchedule
    ): bool;

    public function getViolationMessage(): string;
}
```

### 2.2 `src/Strategy/TeacherConflictConstraint.php`
```php
<?php

declare(strict_types=1);

namespace App\Strategy;

use App\Model\Course;
use App\Model\Classroom;

final class TeacherConflictConstraint implements ScheduleConstraintInterface
{
    private string $lastError = '';

    public function validate(Course $course, Classroom $classroom, string $day, string $timeslot, array $currentSchedule): bool
    {
        foreach ($currentSchedule as $entry) {
            if ($entry['day'] === $day && $entry['timeslot'] === $timeslot) {
                if ($entry['course']->instructorId === $course->instructorId) {
                    $this->lastError = sprintf("Hoca (ID: %d) aynı saatte (%s %s) başka bir derste!", $course->instructorId, $day, $timeslot);
                    return false;
                }
            }
        }
        return true;
    }

    public function getViolationMessage(): string
    {
        return $this->lastError;
    }
}
```

### 2.3 `src/Strategy/RoomCapacityConstraint.php`
```php
<?php

declare(strict_types=1);

namespace App\Strategy;

use App\Model\Course;
use App\Model\Classroom;

final class RoomCapacityConstraint implements ScheduleConstraintInterface
{
    private string $lastError = '';

    public function validate(Course $course, Classroom $classroom, string $day, string $timeslot, array $currentSchedule): bool
    {
        $groupSize = 40; 
        if ($classroom->capacity < $groupSize) {
            $this->lastError = sprintf("Derslik kapasitesi (%d) öğrenci sayısından (%d) yetersiz!", $classroom->capacity, $groupSize);
            return false;
        }

        foreach ($currentSchedule as $entry) {
            if ($entry['day'] === $day && $entry['timeslot'] === $timeslot && $entry['classroom']->id === $classroom->id) {
                $this->lastError = sprintf("Derslik %s aynı saatte dolu!", $classroom->name);
                return false;
            }
        }
        return true;
    }

    public function getViolationMessage(): string
    {
        return $this->lastError;
    }
}
```

---

## 3. Servis Katmanı (`src/Service/`)

### 3.1 `src/Service/ConflictValidatorService.php`
```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Course;
use App\Model\Classroom;
use App\Strategy\ScheduleConstraintInterface;

final class ConflictValidatorService
{
    /** @var ScheduleConstraintInterface[] */
    private array $constraints = [];

    public function addConstraint(ScheduleConstraintInterface $constraint): void
    {
        $this->constraints[] = $constraint;
    }

    public function isValid(Course $course, Classroom $classroom, string $day, string $timeslot, array $currentSchedule): bool
    {
        foreach ($this->constraints as $constraint) {
            if (!$constraint->validate($course, $classroom, $day, $timeslot, $currentSchedule)) {
                return false;
            }
        }
        return true;
    }
}
```

### 3.2 `src/Service/ScheduleSolverService.php`
```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\Contract\CourseRepositoryInterface;
use App\Repository\Contract\ClassroomRepositoryInterface;
use App\Strategy\TeacherConflictConstraint;
use App\Strategy\RoomCapacityConstraint;

final class ScheduleSolverService
{
    private array $days = ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma'];
    private array $timeslots = ['08:30-09:30', '09:30-10:30', '10:30-11:30', '11:30-12:30', '13:30-14:30', '14:30-15:30', '15:30-16:30'];
    private float $startTime;
    private float $timeLimitSeconds = 10.0;

    public function __construct(
        private CourseRepositoryInterface $courseRepository,
        private ClassroomRepositoryInterface $classroomRepository,
        private ConflictValidatorService $validator
    ) {
        $this->validator->addConstraint(new TeacherConflictConstraint());
        $this->validator->addConstraint(new RoomCapacityConstraint());
    }

    public function solve(): array
    {
        $this->startTime = microtime(true);
        $courses = $this->courseRepository->findAll();
        $classrooms = $this->classroomRepository->findAll();
        $schedule = [];

        $success = $this->backtrack($courses, 0, $classrooms, $schedule);

        if (!$success) {
            return [
                'status' => 'error',
                'message' => 'Tıkanıklık Analizi: Belirtilen hoca ve derslik kısıtları altında 10 saniye içerisinde çakışmasız çözüm bulunamadı! Lütfen derslik kapasitelerini veya hoca müsaitliklerini kontrol edin.',
                'schedule' => []
            ];
        }

        return [
            'status' => 'success',
            'executionTime' => round(microtime(true) - $this->startTime, 4) . 's',
            'schedule' => $schedule
        ];
    }

    private function backtrack(array $courses, int $courseIndex, array $classrooms, array &$schedule): bool
    {
        if ((microtime(true) - $this->startTime) > $this->timeLimitSeconds) {
            return false;
        }

        if ($courseIndex >= count($courses)) {
            return true;
        }

        $currentCourse = $courses[$courseIndex];

        foreach ($this->days as $day) {
            foreach ($this->timeslots as $timeslot) {
                foreach ($classrooms as $classroom) {
                    if ($this->validator->isValid($currentCourse, $classroom, $day, $timeslot, $schedule)) {
                        
                        $schedule[] = [
                            'course' => $currentCourse,
                            'classroom' => $classroom,
                            'day' => $day,
                            'timeslot' => $timeslot
                        ];

                        if ($this->backtrack($courses, $courseIndex + 1, $classrooms, $schedule)) {
                            return true;
                        }

                        array_pop($schedule);
                    }
                }
            }
        }

        return false;
    }
}
```

---

## 4. Controller ve Web Root Yapısı

### 4.1 `src/Controller/ScheduleController.php`
```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ScheduleSolverService;

final class ScheduleController
{
    public function __construct(
        private ScheduleSolverService $solverService
    ) {}

    public function generate(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $result = $this->solverService->solve();
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
```

### 4.2 `public/index.php`
```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Repository\Json\JsonCourseRepository;
use App\Repository\Json\JsonClassroomRepository;
use App\Service\ConflictValidatorService;
use App\Service\ScheduleSolverService;
use App\Controller\ScheduleController;

$courseRepo = new JsonCourseRepository();
$classroomRepo = new JsonClassroomRepository();
$validator = new ConflictValidatorService();
$solverService = new ScheduleSolverService($courseRepo, $classroomRepo, $validator);
$controller = new ScheduleController($solverService);

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

if (str_contains($requestUri, '/generate')) {
    $controller->generate();
} else {
    echo "<h1>DPO - Ders Programı Oluşturucu (Aşama 2 JSON)</h1>";
    echo "<p>Otomatik program oluşturmak için <a href='/generate'>/generate</a> uç noktasını çağırın.</p>";
}
```

---

## 5. Proje Ayar Dosyası (`composer.json`)

```json
{
  "name": "esin11/dpo-ders-programi-olusturucu",
  "description": "Ders Programı Oluşturucu - Katmanlı Mimari ve JSON Tabanlı Çizelgeleme Motoru",
  "type": "project",
  "autoload": {
    "psr-4": {
      "App\\": "src/"
    }
  },
  "require": {
    "php": ">=8.2"
  },
  "require-dev": {
    "phpunit/phpunit": "^10.0"
  }
}
```