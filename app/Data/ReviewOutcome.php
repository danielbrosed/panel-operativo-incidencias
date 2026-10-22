<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\IncidentLog;

/** What `incidents:review` recorded for one incident. */
final readonly class ReviewOutcome
{
    /**
     * @param  list<IncidentLog>  $voidedFlags  the flags given as reviewed; empty when there was none to review
     * @param  IncidentLog|null  $reconciledStep  the status_reconciled step signed by the reviewer, if any
     * @param  string|null  $whyNoStep  why no step was written, when flags were reviewed without one
     */
    public function __construct(
        public array $voidedFlags,
        public ?IncidentLog $reconciledStep = null,
        public ?string $whyNoStep = null,
    ) {}

    public function reviewedAnything(): bool
    {
        return $this->voidedFlags !== [];
    }
}
