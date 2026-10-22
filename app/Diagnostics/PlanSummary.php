<?php

declare(strict_types=1);

namespace App\Diagnostics;

/** How many planned actions are in each state, counted once for the report and the exit code. */
final readonly class PlanSummary
{
    public function __construct(
        public int $automatic,
        public int $manual,
        public int $flagPending,
        public int $flagReviewed,
    ) {}

    /** @param  list<PlannedAction>  $plan */
    public static function of(array $plan): self
    {
        $counts = collect($plan)->countBy(fn (PlannedAction $action): string => match (true) {
            $action->isFlagReviewed() => 'flag_reviewed',
            $action->isFlagPending() => 'flag_pending',
            $action->finding->fix->isAutomatic() => 'automatic',
            default => 'manual',
        });

        return new self(
            automatic: $counts->get('automatic', 0),
            manual: $counts->get('manual', 0),
            flagPending: $counts->get('flag_pending', 0),
            flagReviewed: $counts->get('flag_reviewed', 0),
        );
    }

    /** What --fix would still do: correct it or flag it. */
    public function pending(): int
    {
        return $this->automatic + $this->manual;
    }

    public function total(): int
    {
        return $this->pending() + $this->flagPending + $this->flagReviewed;
    }
}
