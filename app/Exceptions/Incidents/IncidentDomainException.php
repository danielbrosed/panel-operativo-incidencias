<?php

declare(strict_types=1);

namespace App\Exceptions\Incidents;

use RuntimeException;

/**
 * A business rule rejected the operation. bootstrap/app.php turns it into a validation error on field(),
 * so the message is shown next to the input that caused it, in the same form, like any other validation error.
 */
abstract class IncidentDomainException extends RuntimeException
{
    abstract public function field(): string;

    /** Conflicts answer 409 to JSON clients; Inertia requests always get a redirect back with the error. */
    public function isConflict(): bool
    {
        return false;
    }
}
