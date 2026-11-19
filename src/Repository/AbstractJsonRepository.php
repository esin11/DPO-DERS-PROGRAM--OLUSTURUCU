<?php
declare(strict_types=1);
namespace App\Repository;

use App\Config\AppConfig;
use App\Exception\RepositoryException;

/**
 * JSON tabanlı repository'lerin ortak CRUD işlemleri.
 * Her alt sınıf $fileName'i ve $entityClass'ı belirler.
 */
abstract class AbstractJsonRepository
{
    protected array $data = [];
    protected string $filePath;

    public function __construct(protected string $fileName)
    {
        $this->filePath = AppConfig::dataDir() . '/' . $fileName;
        $this->load();
    }

    // ── Temel CRUD ──────────────────────────────────────────────

    public function findAll(): array
    {
        return array_values($this->data);
    }

    public function findById(int $id): ?array
    {
        return $this->data[$id] ?? null;
    }

    public function save(array $record): array
    {
        if (empty($record['id'])) {
            $record['id'] = $this->nextId();
        }
        $this->data[$record['id']] = $record;
        $this->persist();
        return $record;
    }

    public function delete(int $id): bool
    {
        if (!isset($this->data[$id])) {
            return false;
        }
        unset($this->data[$id]);
        $this->persist();
        return true;
    }

    public function count(): int
    {
        return count($this->data);
    }

    // ── İç yardımcılar ──────────────────────────────────────────

    protected function load(): void
    {
        if (!file_exists($this->filePath)) {
            $this->data = [];
            return;
        }

        $json = file_get_contents($this->filePath);
        if ($json === false) {
            throw new RepositoryException("JSON dosyası okunamadı: {$this->filePath}");
        }

        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RepositoryException(
                "JSON parse hatası ({$this->filePath}): " . json_last_error_msg()
            );
        }

        // id'yi key olarak tut → O(1) erişim
        $this->data = [];
        foreach ($decoded as $item) {
            $this->data[(int)$item['id']] = $item;
        }
    }

    protected function persist(): void
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new RepositoryException("Dizin oluşturulamadı: {$dir}");
        }

        $json = json_encode(
            array_values($this->data),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if (file_put_contents($this->filePath, $json, LOCK_EX) === false) {
            throw new RepositoryException("JSON dosyasına yazılamadı: {$this->filePath}");
        }
    }

    protected function nextId(): int
    {
        return empty($this->data) ? 1 : max(array_keys($this->data)) + 1;
    }
}
