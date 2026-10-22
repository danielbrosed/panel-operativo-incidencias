<?php

declare(strict_types=1);

namespace App\Diagnostics;

enum FixOutcome
{
    case Applied;

    /** The incident changed between the plan and the fix: re-checked under the lock, the finding is gone. */
    case NoLongerTrue;

    /** Someone flagged the same finding between the plan and the fix. */
    case AlreadyFlagged;

    public function message(): string
    {
        return match ($this) {
            self::Applied => 'aplicada',
            self::NoLongerTrue => 'omitida, el hallazgo ya no se cumple (la incidencia cambió desde el diagnóstico)',
            self::AlreadyFlagged => 'omitida, ya estaba marcada para revisión manual',
        };
    }
}
