<?php
declare(strict_types=1);

namespace App\Services\Api;

use App\Core\Database;
use App\Services\PaymentApiHandler;
use App\Services\ProfileApiHandler as BaseProfileHandler;
use App\Services\TokenAuthMiddleware;
use PDO;

/**
 * Dedicated REST API Profile Handler in App\Services\Api namespace.
 * Injects TokenAuthMiddleware and calls requireOwnership() per Finding 3.8.
 */
class ProfileApiHandler extends BaseProfileHandler
{
    public function __construct(
        ?PDO $pdo = null,
        ?PaymentApiHandler $paymentHandler = null,
        ?TokenAuthMiddleware $tokenAuth = null
    ) {
        parent::__construct($pdo, $paymentHandler, $tokenAuth);
    }
}
