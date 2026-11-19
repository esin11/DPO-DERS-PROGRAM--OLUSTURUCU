<?php
declare(strict_types=1);
namespace Tests\Service;

use App\Repository\AvailabilityRepository;
use App\Repository\CourseRepository;
use App\Repository\RoomRepository;
use App\Repository\ScheduleRepository;
use App\Service\ConflictCheckerService;
use App\Service\SchedulerService;
use App\Exception\NoSolutionException;
use Tests\BaseTestCase;

/**
 * SchedulerServiceTest — Backtracking algoritması testleri.
 *
 * Test senaryoları:
 *  1. Çözüm üretilebilir verilerle başarılı üretim
 *  2. Üretilen programda çakışma olmaması
 *  3. Kasıtlı çözümsüz veri → NoSolutionException + neden raporu
 *  4. Zaman kısıtı içinde üretim (<10s)
 *  5. Hoca müsaitlik kısıtına uyum
 */
class SchedulerServiceTest extends BaseTestCase
{
    private SchedulerService $scheduler;
    private ConflictCheckerService $conflictChecker;

    protected function setUp(): void
    {
        parent::setUp();
        $courseRepo  = new CourseRepository();
        $roomRepo    = new RoomRepository();
        $availRepo   = new AvailabilityRepository();
        $scheduleRepo= new ScheduleRepository();

        $this->conflictChecker = new ConflictCheckerService(
            $courseRepo, $roomRepo, $availRepo, $scheduleRepo
        );
        $this->scheduler = new SchedulerService(
            $courseRepo, $roomRepo, $availRepo, $scheduleRepo, $this->conflictChecker
        );
    }

    protected function seedFixtures(): void
    {
        // 5 hoca
        $this->writeFixture('teachers.json', [
            ['id'=>1,'name'=>'Prof. Ahmet','email'=>'a@t.com','department'=>'BM'],
            ['id'=>2,'name'=>'Doç. Fatma', 'email'=>'f@t.com','department'=>'Matematik'],
            ['id'=>3,'name'=>'Dr. Mehmet',  'email'=>'m@t.com','department'=>'Fizik'],
            ['id'=>4,'name'=>'Arş. Ayşe',  'email'=>'ay@t.com','department'=>'BM'],
            ['id'=>5,'name'=>'Prof. Hasan', 'email'=>'h@t.com','department'=>'EE'],
        ]);

        // 4 derslik
        $this->writeFixture('rooms.json', [
            ['id'=>1,'name'=>'D-101','capacity'=>40,'building'=>'D','room_type'=>'classroom'],
            ['id'=>2,'name'=>'D-102','capacity'=>35,'building'=>'D','room_type'=>'classroom'],
            ['id'=>3,'name'=>'LAB-1','capacity'=>30,'building'=>'A','room_type'=>'lab'],
            ['id'=>4,'name'=>'Amfi', 'capacity'=>120,'building'=>'B','room_type'=>'auditorium'],
        ]);

        // 3 grup
        $this->writeFixture('student_groups.json', [
            ['id'=>1,'name'=>'BM-1A','grade'=>1,'student_count'=>30,'department'=>'BM'],
            ['id'=>2,'name'=>'BM-2A','grade'=>2,'student_count'=>28,'department'=>'BM'],
            ['id'=>3,'name'=>'EE-1A','grade'=>1,'student_count'=>25,'department'=>'EE'],
        ]);

        // 12 ders (her biri 1 saat/hafta → toplamda 12 slot gerekir, 45 slot var)
        $this->writeFixture('courses.json', [
            ['id'=>1, 'code'=>'CSE101','name'=>'Prog. Giriş',    'teacher_id'=>1,'student_group_id'=>1,'weekly_hours'=>1,'required_capacity'=>30,'course_type'=>'theory'],
            ['id'=>2, 'code'=>'CSE102','name'=>'Prog. Lab',      'teacher_id'=>4,'student_group_id'=>1,'weekly_hours'=>1,'required_capacity'=>30,'course_type'=>'lab'],
            ['id'=>3, 'code'=>'MTH101','name'=>'Calculus I',     'teacher_id'=>2,'student_group_id'=>1,'weekly_hours'=>1,'required_capacity'=>30,'course_type'=>'theory'],
            ['id'=>4, 'code'=>'PHY101','name'=>'Fizik I',        'teacher_id'=>3,'student_group_id'=>1,'weekly_hours'=>1,'required_capacity'=>30,'course_type'=>'theory'],
            ['id'=>5, 'code'=>'CSE201','name'=>'Veri Yapıları',  'teacher_id'=>1,'student_group_id'=>2,'weekly_hours'=>1,'required_capacity'=>28,'course_type'=>'theory'],
            ['id'=>6, 'code'=>'CSE202','name'=>'Algoritmalar',   'teacher_id'=>4,'student_group_id'=>2,'weekly_hours'=>1,'required_capacity'=>28,'course_type'=>'theory'],
            ['id'=>7, 'code'=>'MTH201','name'=>'Lineer Cebir',   'teacher_id'=>2,'student_group_id'=>2,'weekly_hours'=>1,'required_capacity'=>28,'course_type'=>'theory'],
            ['id'=>8, 'code'=>'CSE203','name'=>'OOP',            'teacher_id'=>1,'student_group_id'=>2,'weekly_hours'=>1,'required_capacity'=>28,'course_type'=>'theory'],
            ['id'=>9, 'code'=>'EEE101','name'=>'Devre Teorisi',  'teacher_id'=>5,'student_group_id'=>3,'weekly_hours'=>1,'required_capacity'=>25,'course_type'=>'theory'],
            ['id'=>10,'code'=>'EEE102','name'=>'Elektronik',     'teacher_id'=>5,'student_group_id'=>3,'weekly_hours'=>1,'required_capacity'=>25,'course_type'=>'theory'],
            ['id'=>11,'code'=>'MTH102','name'=>'Calculus II',    'teacher_id'=>2,'student_group_id'=>3,'weekly_hours'=>1,'required_capacity'=>25,'course_type'=>'theory'],
            ['id'=>12,'code'=>'PHY102','name'=>'Fizik II',       'teacher_id'=>3,'student_group_id'=>3,'weekly_hours'=>1,'required_capacity'=>25,'course_type'=>'theory'],
        ]);

        $this->writeFixture('availability.json', []);
        $this->writeFixture('schedule_entries.json', []);
    }

    // ─── Test 1: Başarılı program üretimi ────────────────────────────────────

    public function test_generate_returns_entries_for_all_courses(): void
    {
        $entries = $this->scheduler->generate();

        $this->assertNotEmpty($entries, 'Program üretilmelidir.');
        $this->assertCount(12, $entries, '12 ders için 12 entry bekleniyor (1 saat/ders).');
    }

    // ─── Test 2: Üretilen programda çakışma yok ──────────────────────────────

    public function test_generated_schedule_has_no_conflicts(): void
    {
        $this->scheduler->generate();

        $scheduleRepo    = new ScheduleRepository();
        $version         = $scheduleRepo->getLatestVersion();
        $conflicts       = $this->conflictChecker->findAllConflicts($version);

        $conflictMessages = array_map(fn($c) => $c->message, $conflicts);
        $this->assertEmpty($conflicts, "Çakışma bulundu:\n" . implode("\n", $conflictMessages));
    }

    // ─── Test 3: Kasıtlı çözümsüz veri → NoSolutionException ────────────────

    public function test_generate_throws_no_solution_when_impossible(): void
    {
        // Sadece 1 küçük oda (kapasite=1) var, dersler 25+ kişi gerektiriyor
        $this->writeFixture('rooms.json', [
            ['id'=>1,'name'=>'Küçük-1','capacity'=>1,'building'=>'X','room_type'=>'classroom'],
        ]);

        $this->expectException(NoSolutionException::class);
        $this->scheduler->generate();
    }

    // ─── Test 4: NoSolutionException neden raporu içeriyor ───────────────────

    public function test_no_solution_exception_contains_reasons(): void
    {
        $this->writeFixture('rooms.json', [
            ['id'=>1,'name'=>'Küçük-1','capacity'=>1,'building'=>'X','room_type'=>'classroom'],
        ]);

        try {
            $this->scheduler->generate();
            $this->fail('NoSolutionException bekleniyor.');
        } catch (NoSolutionException $e) {
            $this->assertNotEmpty($e->getReasons(), 'Neden listesi boş olmamalı.');
            $this->assertIsArray($e->getReasons());
        }
    }

    // ─── Test 5: Üretim 10 saniyenin altında tamamlanır ─────────────────────

    public function test_generation_completes_within_time_limit(): void
    {
        $start   = microtime(true);
        $this->scheduler->generate();
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(10.0, $elapsed,
            "Üretim {$elapsed}s sürdü, 10s limitini aştı.");
    }

    // ─── Test 6: Hoca müsaitlik kısıtına uyum ────────────────────────────────

    public function test_generated_schedule_respects_teacher_availability(): void
    {
        // Hoca 1'i Pazartesi sabah (slot 1,2,3) müsait değil olarak işaretle
        $this->writeFixture('availability.json', [
            ['id'=>1,'teacher_id'=>1,'day_of_week'=>'Pazartesi','time_slot'=>1,'is_available'=>false],
            ['id'=>2,'teacher_id'=>1,'day_of_week'=>'Pazartesi','time_slot'=>2,'is_available'=>false],
            ['id'=>3,'teacher_id'=>1,'day_of_week'=>'Pazartesi','time_slot'=>3,'is_available'=>false],
        ]);

        $entries = $this->scheduler->generate();

        // Hoca 1'e ait derslerin hiçbiri Pazartesi 1-3. slotta olmamalı
        $courseRepo = new CourseRepository();
        $teacher1Courses = array_map(
            fn($c) => $c->id,
            $courseRepo->findByTeacher(1)
        );

        foreach ($entries as $entry) {
            if (in_array($entry->courseId, $teacher1Courses)) {
                $this->assertFalse(
                    $entry->dayOfWeek === 'Pazartesi' && in_array($entry->timeSlot, [1, 2, 3]),
                    "Hoca 1 Pazartesi {$entry->timeSlot}. saatte müsait değil ama atanmış."
                );
            }
        }
    }

    // ─── Test 7: Aynı hoca aynı gün+slot'ta iki derse atanmaz ───────────────

    public function test_no_teacher_double_booking(): void
    {
        $entries = $this->scheduler->generate();

        $courseRepo = new CourseRepository();
        $slotMap    = []; // teacher_id → [day+slot → course_id]

        foreach ($entries as $entry) {
            $course  = $courseRepo->findById($entry->courseId);
            $key     = $entry->dayOfWeek . '_' . $entry->timeSlot;
            $tid     = $course->teacherId;

            $this->assertArrayNotHasKey(
                $key,
                $slotMap[$tid] ?? [],
                "Hoca #{$tid} {$entry->dayOfWeek} {$entry->timeSlot}. saatte çift atanmış."
            );
            $slotMap[$tid][$key] = $entry->courseId;
        }
    }
}
