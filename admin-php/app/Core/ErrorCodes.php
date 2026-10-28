<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Standardized HTTP and API error codes.
 */
class ErrorCodes
{
    public const BAD_REQUEST          = 400;
    public const UNAUTHORIZED         = 401;
    public const FORBIDDEN            = 403;
    public const NOT_FOUND            = 404;
    public const METHOD_NOT_ALLOWED   = 405;
    public const UNPROCESSABLE_ENTITY = 422;
    public const TOO_MANY_REQUESTS    = 429;
    public const INTERNAL_ERROR       = 500;
}
