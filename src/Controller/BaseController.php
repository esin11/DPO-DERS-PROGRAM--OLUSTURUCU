<?php
declare(strict_types=1);
namespace App\Controller;

/**
 * Tüm controller'ların temel sınıfı.
 * JSON yanıt yardımcıları ve basit routing altyapısı burada.
 */
abstract class BaseController
{
    protected function jsonResponse(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function jsonSuccess(mixed $data = null, string $message = 'Başarılı.'): void
    {
        $this->jsonResponse([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ]);
    }

    protected function jsonError(string $message, int $status = 400, array $extra = []): void
    {
        $this->jsonResponse(
            array_merge(['success' => false, 'message' => $message], $extra),
            $status
        );
    }

    protected function getBody(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw ?: '', true);
        return is_array($decoded) ? $decoded : $_POST;
    }

    protected function requireMethod(string $method): bool
    {
        if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
            $this->jsonError('Method Not Allowed', 405);
            return false;
        }
        return true;
    }
}
