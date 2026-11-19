<?php
declare(strict_types=1);
namespace App\Exception;

/**
 * Backtracking algoritması geçerli bir çözüm bulamadığında fırlatılır.
 * reasons → neden çözüm bulunamadığına dair açıklamalar listesi.
 */
class NoSolutionException extends ScheduleException
{
    public function __construct(
        string $message = 'Verilen kısıtlarla geçerli bir ders programı oluşturulamadı.',
        private readonly array $reasons = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getReasons(): array
    {
        return $this->reasons;
    }
}
