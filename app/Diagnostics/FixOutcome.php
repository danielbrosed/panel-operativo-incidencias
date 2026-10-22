<?php

declare(strict_types=1);

namespace App\Diagnostics;

use App\Diagnostics\Fixes\Fix;

enum FixOutcome
{
    case Applied;

    /** The incident changed between the plan and the fix: re-checked under the lock, the finding is gone. */
    case NoLongerTrue;

    /** Someone flagged the same finding between the plan and the fix. */
    case AlreadyFlagged;

    /** A person reviewed the same finding (incidents:review) between the plan and the fix. */
    case AlreadyReviewed;

    public function message(Fix $fix): string
    {
        return match ($this) {
            self::Applied => $fix->appliedMessage(),
            self::NoLongerTrue => 'omitida, el hallazgo ya no se cumple (la incidencia cambió desde el diagnóstico)',
            self::AlreadyFlagged => 'omitida, ya estaba marcada para revisión manual',
            self::AlreadyReviewed => 'omitida, ya estaba revisada',
        };
    }
}
