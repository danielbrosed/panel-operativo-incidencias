<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasOption;

/**
 * Incident lifecycle and the single source of truth for the state machine: the actions enforce it, the resources
 * send each incident's transitions to the UI and the diagnostic judges the gaps in the trail with it.
 *
 *   open ──► in_review ──► resolved ──(reopen, reason)──► open
 *                │  ▲
 *        (reason)▼  │
 *              blocked
 */
enum IncidentStatus: string
{
    use HasOption;

    case Open = 'open';
    case InReview = 'in_review';
    case Blocked = 'blocked';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Abierta',
            self::InReview => 'En revisión',
            self::Blocked => 'Bloqueada',
            self::Resolved => 'Resuelta',
        };
    }

    /** The label for use inside a sentence. */
    public function lowerLabel(): string
    {
        return mb_strtolower($this->label());
    }

    /** lowerLabel() for a value read from the database, which may not be a case: an unknown value is shown as stored. */
    public static function lowerLabelOf(?string $value): string
    {
        if ($value === null) {
            return 'sin valor';
        }

        return self::tryFrom($value)?->lowerLabel() ?? $value;
    }

    /**
     * A blocked incident goes back to review before it can be resolved, so every resolution
     * follows active work and the trail shows who reviewed it.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Open => [self::InReview],
            self::InReview => [self::Blocked, self::Resolved],
            self::Blocked => [self::InReview],
            self::Resolved => [self::Open],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function isReopening(self $to): bool
    {
        return $this === self::Resolved && $to === self::Open;
    }

    /**
     * Blocking needs a reason because it is what the next person needs to unblock it;
     * reopening needs one so that the reopen is explicit in the trail.
     */
    public function requiresReason(self $to): bool
    {
        return $to === self::Blocked || $this->isReopening($to);
    }

    /** Transitions with consequences ask for confirmation in the UI. */
    public function requiresConfirmation(self $to): bool
    {
        return $to === self::Blocked || $to === self::Resolved || $this->isReopening($to);
    }

    /** The UI shows it as a destructive action: blocking stops the work on the incident. */
    public function isDestructive(self $to): bool
    {
        return $to === self::Blocked;
    }

    public function logActionFor(self $to): IncidentLogAction
    {
        return $this->isReopening($to) ? IncidentLogAction::Reopened : IncidentLogAction::StatusChanged;
    }

    /** Verb shown on the button that performs the transition. */
    public function actionLabel(self $to): string
    {
        return match (true) {
            $this->isReopening($to) => 'Reabrir',
            $this === self::Blocked && $to === self::InReview => 'Desbloquear',
            $to === self::InReview => 'Pasar a revisión',
            $to === self::Blocked => 'Bloquear',
            $to === self::Resolved => 'Resolver',
            default => $to->label(),
        };
    }
}
