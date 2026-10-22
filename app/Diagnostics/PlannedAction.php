<?php

declare(strict_types=1);

namespace App\Diagnostics;

use App\Diagnostics\Checks\ConsistencyCheck;
use App\Models\IncidentLog;

/** A finding together with what --fix will do about it. The dry run prints the plan; --fix applies it. */
final readonly class PlannedAction
{
    /** @param  IncidentLog|null  $flag  the flag already left for the same finding: active (in review) or voided (reviewed) */
    public function __construct(
        public ConsistencyCheck $check,
        public Finding $finding,
        public ?IncidentLog $flag = null,
    ) {}

    /** A finding already flagged or reviewed is still listed, but there is nothing left for --fix to do. */
    public function isActionable(): bool
    {
        return $this->flag === null;
    }

    public function isInReview(): bool
    {
        return $this->flag !== null && ! $this->flag->isVoided();
    }

    public function isReviewed(): bool
    {
        return $this->flag !== null && $this->flag->isVoided();
    }

    public function describe(): string
    {
        return match (true) {
            $this->flag === null => $this->finding->fix->describe(),
            // The void reason already reads "Revisada por {actor}: {reason}".
            $this->flag->voided_at !== null => sprintf(
                '%s (%s).',
                rtrim((string) $this->flag->voided_reason, '.'),
                $this->flag->voided_at->format('d/m/Y H:i T'),
            ),
            default => sprintf('Ya marcada para revisión manual (%s).', $this->flag->created_at->format('d/m/Y H:i T')),
        };
    }
}
