<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Incident lifecycle. This enum is the single source of truth for the state machine:
 * the actions enforce it, the API resources send the allowed transitions to the UI,
 * and the diagnostic uses it to judge whether a gap in the audit trail is explainable.
 *
 *   open ──► in_review ──► resolved ──(reopen, reason)──► open
 *                │  ▲
 *        (reason)▼  │
 *              blocked
 */
enum IncidentStatus: string
{
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
