<?php

declare(strict_types=1);

namespace App\Diagnostics;

use App\Diagnostics\Checks\ConsistencyCheck;
use Carbon\CarbonImmutable;

/** A finding together with what --fix will do about it. The dry run prints the plan; --fix applies it. */
final readonly class PlannedAction
{
    /** @param  CarbonImmutable|null  $flaggedAt  when the same finding was already flagged for manual review */
    public function __construct(
        public ConsistencyCheck $check,
        public Finding $finding,
        public ?CarbonImmutable $flaggedAt = null,
    ) {}

    /** An incident already flagged is still listed, but there is nothing left for --fix to do. */
    public function isActionable(): bool
    {
        return $this->flaggedAt === null;
    }

    public function describe(): string
    {
        return $this->isActionable()
            ? $this->finding->fix->describe()
            : 'Ya marcada para revisión manual ('.$this->flaggedAt?->format('d/m/Y H:i T').').';
    }
}
