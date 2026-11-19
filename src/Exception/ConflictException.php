<?php
declare(strict_types=1);
namespace App\Exception;

/**
 * Kısıtları ihlal eden bir çakışma tespit edildiğinde fırlatılır.
 * message → kullanıcıya gösterilecek Türkçe açıklama.
 * context → çakışmanın detayları (ders, hoca, oda, gün, slot).
 */
class ConflictException extends ScheduleException
{
    public function __construct(
        string $message,
        private readonly array $context = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
