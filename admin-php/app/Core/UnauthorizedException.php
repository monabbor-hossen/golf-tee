<?php
declare(strict_types=1);

namespace App\Core;

use Exception;

/**
 * UnauthorizedException (401)
 */
class UnauthorizedException extends Exception
{
    public function __construct(string $message = 'Unauthorized', int $code = 401, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
