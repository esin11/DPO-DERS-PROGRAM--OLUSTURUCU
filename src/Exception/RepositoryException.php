<?php
declare(strict_types=1);
namespace App\Exception;

/**
 * Repository katmanında JSON okuma/yazma hatalarında fırlatılır.
 */
class RepositoryException extends \RuntimeException {}
