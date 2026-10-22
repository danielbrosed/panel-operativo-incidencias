<?php

declare(strict_types=1);

namespace App\Diagnostics\Fixes;

use App\Diagnostics\Finding;
use App\Models\Incident;

/** Voids the later copy of a duplicated trail entry. The row stays, struck through, with the reason. */
final readonly class VoidDuplicateLog implements Fix
{
    public function __construct(
        public int $logId,
        public int $originalLogId,
    ) {}

    public function isAutomatic(): bool
    {
        return true;
    }

    public function describe(): string
    {
        return "Automática. Se anula el log #{$this->logId} como duplicado del #{$this->originalLogId}; no se borra.";
    }

    public function appliedMessage(): string
    {
        return "corregida, anulado el log #{$this->logId} como duplicado del #{$this->originalLogId}";
    }

    public function apply(Incident $incident, Finding $finding): void
    {
        $incident->logs()->whereKey($this->logId)->firstOrFail()
            ->void("Duplicado del log #{$this->originalLogId} (incidents:diagnose)");
    }
}
