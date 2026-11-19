<?php
declare(strict_types=1);
namespace Tests\Service;

use App\Entity\Course;
use App\Entity\ScheduleEntry;
use App\Repository\AvailabilityRepository;
use App\Repository\CourseRepository;
use App\Repository\RoomRepository;
use App\Repository\ScheduleRepository;
use App\Service\ConflictCheckerService;
use Tests\BaseTestCase;

/**
 * ConflictCheckerServiceTest — Çakışma tespit servisi testleri.
 *
 * Test senaryoları:
 *  1. Çakışmasız programda boş liste döner
 *  2. Hoca çakışması tespit edilir
 *  3. Oda çakışması tespit edilir
 *  4. Grup çakışması tespit edilir
 *  5. Kapasite yetersizliği tespit edilir
 *  6. Müsaitlik ihlali tespit edilir
 *  7. checkSingleAssignment anlık çakışma döner
 */
class ConflictCheckerServiceTest extends BaseTestCase
{
    private ConflictCheckerService $checker;
    private CourseRepository $courseRepo;
    private RoomRepository $roomRepo;
    private AvailabilityRepository $availRepo;
    private ScheduleRepository $scheduleRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->courseRepo   = new CourseRepository();
        $this->roomRepo     = new RoomRepository();
        $this->availRepo    = new AvailabilityRepository();
        $this->scheduleRepo = new ScheduleRepository();
        $this->checker      = new ConflictCheckerService(
            $this->courseRepo, $this->roomRepo, $this->availRepo, $this->scheduleRepo
        );
    }

    protected function seedFixtures(): void
    {
        $this->writeFixture('teachers.json', [
            ['id'=>1,'name'=>'Hoca A','email'=>'a@t.com','department'=>'BM'],
            ['id'=>2,'name'=>'Hoca B','email'=>'b@t.com','department'=>'MM'],
        ]);
        $this->writeFixture('rooms.json', [
            ['id'=>1,'name'=>'D-101','capacity'=>30,'building'=>'D','room_type'=>'classroom'],
            ['id'=>2,'name'=>'D-102','capacity'=>10,'building'=>'D','room_type'=>'classroom'],
        ]);
        $this->writeFixture('student_groups.json', [
            ['id'=>1,'name'=>'GRP-A','grade'=>1,'student_count'=>25,'department'=>'BM'],
            ['id'=>2,'name'=>'GRP-B','grade'=>1,'student_count'=>25,'department'=>'MM'],
        ]);
        $this->writeFixture('courses.json', [
            ['id'=>1,'code'=>'C1','name'=>'Ders 1','teacher_id'=>1,'student_group_id'=>1,'weekly_hours'=>1,'required_capacity'=>25,'course_type'=>'theory'],
            ['id'=>2,'code'=>'C2','name'=>'Ders 2','teacher_id'=>1,'student_group_id'=>2,'weekly_hours'=>1,'required_capacity'=>25,'course_type'=>'theory'],
            ['id'=>3,'code'=>'C3','name'=>'Ders 3','teacher_id'=>2,'student_group_id'=>1,'weekly_hours'=>1,'required_capacity'=>25,'course_type'=>'theory'],
            ['id'=>4,'code'=>'C4','name'=>'Ders 4','teacher_id'=>2,'student_group_id'=>2,'weekly_hours'=>1,'required_capacity'=>35,'course_type'=>'theory'],
        ]);
        $this->writeFixture('availability.json', [
            ['id'=>1,'teacher_id'=>1,'day_of_week'=>'Pazartesi','time_slot'=>1,'is_available'=>false],
        ]);
        $this->writeFixture('schedule_entries.json', []);
    }

    // ─── Test 1: Boş program → çakışma yok ──────────────────────────────────

    public function test_empty_schedule_has_no_conflicts(): void
    {
        $conflicts = $this->checker->findAllConflicts(1);
        $this->assertEmpty($conflicts, 'Boş programda çakışma olmamalı.');
    }

    // ─── Test 2: Hoca çakışması ───────────────────────────────────────────────

    public function test_detects_teacher_conflict(): void
    {
        // Hoca 1 → iki farklı derse aynı gün+slot atanmış
        $this->writeFixture('schedule_entries.json', [
            ['id'=>1,'course_id'=>1,'room_id'=>1,'day_of_week'=>'Pazartesi','time_slot'=>2,'schedule_version'=>1],
            ['id'=>2,'course_id'=>2,'room_id'=>2,'day_of_week'=>'Pazartesi','time_slot'=>2,'schedule_version'=>1],
        ]);

        $conflicts = $this->checker->findAllConflicts(1);
        $types     = array_column(array_map(fn($c) => $c->toArray(), $conflicts), 'type');

        $this->assertContains('teacher', $types, 'Hoca çakışması tespit edilmeli.');
    }

    // ─── Test 3: Oda çakışması ───────────────────────────────────────────────

    public function test_detects_room_conflict(): void
    {
        // İki farklı ders aynı oda+gün+slot
        $this->writeFixture('schedule_entries.json', [
            ['id'=>1,'course_id'=>1,'room_id'=>1,'day_of_week'=>'Salı','time_slot'=>3,'schedule_version'=>1],
            ['id'=>2,'course_id'=>3,'room_id'=>1,'day_of_week'=>'Salı','time_slot'=>3,'schedule_version'=>1],
        ]);

        $conflicts = $this->checker->findAllConflicts(1);
        $types     = array_column(array_map(fn($c) => $c->toArray(), $conflicts), 'type');

        $this->assertContains('room', $types, 'Oda çakışması tespit edilmeli.');
    }

    // ─── Test 4: Grup çakışması ───────────────────────────────────────────────

    public function test_detects_group_conflict(): void
    {
        // GRP-A → iki farklı ders aynı gün+slot
        $this->writeFixture('schedule_entries.json', [
            ['id'=>1,'course_id'=>1,'room_id'=>1,'day_of_week'=>'Çarşamba','time_slot'=>4,'schedule_version'=>1],
            ['id'=>2,'course_id'=>3,'room_id'=>2,'day_of_week'=>'Çarşamba','time_slot'=>4,'schedule_version'=>1],
        ]);

        $conflicts = $this->checker->findAllConflicts(1);
        $types     = array_column(array_map(fn($c) => $c->toArray(), $conflicts), 'type');

        $this->assertContains('group', $types, 'Grup çakışması tespit edilmeli.');
    }

    // ─── Test 5: Kapasite yetersizliği ───────────────────────────────────────

    public function test_detects_capacity_violation(): void
    {
        // Ders 4 → 35 kişi, Oda 1 → 30 kişi (yetersiz)
        $this->writeFixture('schedule_entries.json', [
            ['id'=>1,'course_id'=>4,'room_id'=>1,'day_of_week'=>'Perşembe','time_slot'=>5,'schedule_version'=>1],
        ]);

        $conflicts = $this->checker->findAllConflicts(1);
        $types     = array_column(array_map(fn($c) => $c->toArray(), $conflicts), 'type');

        $this->assertContains('capacity', $types, 'Kapasite ihlali tespit edilmeli.');
    }

    // ─── Test 6: Müsaitlik ihlali ─────────────────────────────────────────────

    public function test_detects_availability_violation(): void
    {
        // Hoca 1, Pazartesi slot 1'de müsait değil (fixture'da var)
        $this->writeFixture('schedule_entries.json', [
            ['id'=>1,'course_id'=>1,'room_id'=>1,'day_of_week'=>'Pazartesi','time_slot'=>1,'schedule_version'=>1],
        ]);

        $conflicts = $this->checker->findAllConflicts(1);
        $types     = array_column(array_map(fn($c) => $c->toArray(), $conflicts), 'type');

        $this->assertContains('teacher', $types, 'Müsaitlik ihlali tespit edilmeli.');
    }

    // ─── Test 7: Çakışmasız program → boş çakışma listesi ───────────────────

    public function test_valid_schedule_returns_no_conflicts(): void
    {
        // Her ders farklı gün+slot+oda
        $this->writeFixture('schedule_entries.json', [
            ['id'=>1,'course_id'=>1,'room_id'=>1,'day_of_week'=>'Pazartesi','time_slot'=>2,'schedule_version'=>1],
            ['id'=>2,'course_id'=>2,'room_id'=>1,'day_of_week'=>'Salı',    'time_slot'=>3,'schedule_version'=>1],
            ['id'=>3,'course_id'=>3,'room_id'=>2,'day_of_week'=>'Çarşamba','time_slot'=>4,'schedule_version'=>1],
        ]);

        $conflicts = $this->checker->findAllConflicts(1);
        $this->assertEmpty($conflicts, 'Geçerli programda çakışma olmamalı.');
    }

    // ─── Test 8: checkSingleAssignment → anlık çakışma döner ─────────────────

    public function test_check_single_detects_teacher_conflict(): void
    {
        $existingEntry = new ScheduleEntry(1, 1, 1, 'Pazartesi', 3, 1); // Hoca 1'in dersi
        $newCourse     = $this->courseRepo->findById(2); // Hoca 1'e ait başka ders

        $conflicts = $this->checker->checkSingleAssignment(
            $newCourse, 2, 'Pazartesi', 3, [$existingEntry]
        );

        $types = array_column(array_map(fn($c) => $c->toArray(), $conflicts), 'type');
        $this->assertContains('teacher', $types, 'Anlık hoca çakışması tespit edilmeli.');
    }
}
