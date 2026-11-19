<?php
declare(strict_types=1);
namespace Tests\Repository;

use App\Entity\Teacher;
use App\Repository\TeacherRepository;
use Tests\BaseTestCase;

/**
 * AbstractJsonRepository — CRUD işlemleri testleri.
 */
class TeacherRepositoryTest extends BaseTestCase
{
    private TeacherRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->writeFixture('teachers.json', [
            ['id'=>1,'name'=>'Hoca A','email'=>'a@t.com','department'=>'BM'],
        ]);
        // diğer fixture'lar boş
        foreach (['rooms','courses','student_groups','availability','schedule_entries'] as $f) {
            $this->writeFixture("{$f}.json", []);
        }
        $this->repo = new TeacherRepository();
    }

    public function test_find_all_returns_all_teachers(): void
    {
        $teachers = $this->repo->findAll();
        $this->assertCount(1, $teachers);
        $this->assertInstanceOf(Teacher::class, $teachers[0]);
        $this->assertEquals('Hoca A', $teachers[0]->name);
    }

    public function test_find_by_id_returns_correct_teacher(): void
    {
        $teacher = $this->repo->findById(1);
        $this->assertNotNull($teacher);
        $this->assertEquals(1, $teacher->id);
    }

    public function test_find_by_id_returns_null_for_missing(): void
    {
        $this->assertNull($this->repo->findById(999));
    }

    public function test_save_creates_new_teacher_with_auto_id(): void
    {
        $new = new Teacher(0, 'Hoca B', 'b@t.com', 'Fizik');
        $saved = $this->repo->save($new);
        $this->assertEquals(2, $saved->id);
        $this->assertCount(2, $this->repo->findAll());
    }

    public function test_save_updates_existing_teacher(): void
    {
        $updated = new Teacher(1, 'Hoca A Güncellendi', 'a@t.com', 'BM');
        $this->repo->save($updated);
        $teacher = $this->repo->findById(1);
        $this->assertEquals('Hoca A Güncellendi', $teacher->name);
    }

    public function test_delete_removes_teacher(): void
    {
        $result = $this->repo->delete(1);
        $this->assertTrue($result);
        $this->assertNull($this->repo->findById(1));
        $this->assertCount(0, $this->repo->findAll());
    }

    public function test_delete_returns_false_for_missing(): void
    {
        $this->assertFalse($this->repo->delete(999));
    }

    public function test_count_returns_correct_number(): void
    {
        $this->assertEquals(1, $this->repo->count());
        $this->repo->save(new Teacher(0, 'B', 'b@t.com', 'X'));
        $this->assertEquals(2, $this->repo->count());
    }
}
