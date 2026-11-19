<?php
declare(strict_types=1);
namespace Tests;

use PHPUnit\Framework\TestCase;

/**
 * Test tabanı: test fixture JSON dosyalarını hazırlar ve temizler.
 */
abstract class BaseTestCase extends TestCase
{
    protected string $fixtureDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtureDir = __DIR__ . '/fixtures';
        if (!is_dir($this->fixtureDir)) {
            mkdir($this->fixtureDir, 0755, true);
        }
        // DATA_DIR'i fixture'a yönlendir
        $_ENV['DATA_DIR'] = $this->fixtureDir;
        $_ENV['APP_ENV']  = 'testing';
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Fixture JSON'larını temizle
        foreach (glob($this->fixtureDir . '/*.json') as $f) {
            unlink($f);
        }
    }

    protected function seedFixtures(): void
    {
        // Alt sınıflar override edebilir
    }

    protected function writeFixture(string $filename, array $data): void
    {
        file_put_contents(
            $this->fixtureDir . '/' . $filename,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}
