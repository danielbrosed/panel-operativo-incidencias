<?php

declare(strict_types=1);

namespace App\Exceptions\Incidents;

use RuntimeException;

/** A business rule rejected the operation. bootstrap/app.php turns it into a validation error on field(). */
abstract class IncidentRuleViolation extends RuntimeException
{
    abstract public function field(): string;
}
