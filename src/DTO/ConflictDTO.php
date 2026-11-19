<?php
declare(strict_types=1);
namespace App\DTO;

/**
 * Çakışma tespit sonucu DTO'su — AJAX yanıtlarında kullanılır.
 */
class ConflictDTO
{
    public function __construct(
        public readonly string $type,       // 'teacher' | 'room' | 'group' | 'capacity'
        public readonly string $message,    // Kullanıcıya gösterilecek Türkçe mesaj
        public readonly array  $context = [],
    ) {}

    public function toArray(): array
    {
        return [
            'type'    => $this->type,
            'message' => $this->message,
            'context' => $this->context,
        ];
    }
}
