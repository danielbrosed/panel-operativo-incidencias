<?php

declare(strict_types=1);

namespace App\Enums;

enum IncidentLogAction: string
{
    /** null → open, written in the same transaction that creates the incident. */
    case Created = 'created';

    /** A transition made by a person from the panel. */
    case StatusChanged = 'status_changed';

    /** resolved → open. Kept apart from status_changed so a reopen is never implicit. */
    case Reopened = 'reopened';

    /**
     * Missing step written after the fact, always dated when it was written. Either rebuilt by
     * `incidents:diagnose --fix` (actor sistema:diagnostico, metadata.inferred = true) or stated by a person with
     * `incidents:review` (their name, metadata.reviewed = true and their reason).
     */
    case StatusReconciled = 'status_reconciled';

    /** Inconsistency the diagnostic could not fix safely; a person has to decide. Does not change the status. */
    case FlaggedForReview = 'flagged_for_review';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Incidencia abierta',
            self::StatusChanged => 'Cambio de estado',
            self::Reopened => 'Reapertura',
            self::StatusReconciled => 'Estado conciliado',
            self::FlaggedForReview => 'Marcada para revisión manual',
        };
    }

    /**
     * Actions that record a status. Their latest non-voided entry must match incidents.status:
     * that is what the diagnostic checks.
     *
     * @return list<self>
     */
    public static function statusTrail(): array
    {
        return [self::Created, self::StatusChanged, self::Reopened, self::StatusReconciled];
    }

    /** @return list<string> */
    public static function statusTrailValues(): array
    {
        return array_map(fn (self $action): string => $action->value, self::statusTrail());
    }
}
