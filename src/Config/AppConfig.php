<?php
declare(strict_types=1);
namespace App\Config;

class AppConfig
{
    public const DAYS = ['Pazartesi','Salı','Çarşamba','Perşembe','Cuma'];

    public const TIME_SLOTS = [
        1 => '08:00', 2 => '09:00', 3 => '10:00',
        4 => '11:00', 5 => '12:00', 6 => '13:00',
        7 => '14:00', 8 => '15:00', 9 => '16:00',
    ];

    /** Backtracking iterasyon limiti */
    public const BACKTRACK_LIMIT = 500_000;

    /** Otomatik üretim zaman aşımı (saniye) */
    public const GENERATION_TIMEOUT = 10;

    /** JSON veri dizini (env'den override edilebilir) */
    public static function dataDir(): string
    {
        return $_ENV['DATA_DIR'] ?? dirname(__DIR__, 2) . '/data';
    }
}
