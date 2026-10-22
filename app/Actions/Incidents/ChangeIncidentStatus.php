<?php

declare(strict_types=1);

namespace App\Actions\Incidents;

use App\Data\Actor;
use App\Enums\IncidentStatus;
use App\Exceptions\Incidents\InvalidStatusTransition;
use App\Exceptions\Incidents\ReasonRequired;
use App\Exceptions\Incidents\StaleIncidentStatus;
use App\Models\Incident;
use Illuminate\Support\Facades\DB;

final class ChangeIncidentStatus
{
    /**
     * The only way an incident changes status.
     *
     * The row is locked (SELECT … FOR UPDATE) and re-read inside the transaction, so two people acting at the
     * same time are serialised: the second one sees the first one's result and gets a clear conflict instead
     * of a duplicated or contradictory log. The status and its log are committed together or not at all.
     *
     * @param  IncidentStatus  $expected  the status the person was looking at when they acted
     *
     * @throws StaleIncidentStatus
     * @throws InvalidStatusTransition
     * @throws ReasonRequired
     */
    public function handle(
        Incident $incident,
        IncidentStatus $to,
        IncidentStatus $expected,
        Actor $actor,
        ?string $reason = null,
    ): Incident {
        $reason = $reason !== null && trim($reason) !== '' ? trim($reason) : null;

        return DB::transaction(function () use ($incident, $to, $expected, $actor, $reason): Incident {
            $locked = Incident::query()->whereKey($incident->getKey())->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            if ($from !== $expected) {
                throw StaleIncidentStatus::for($from);
            }

            if (! $from->canTransitionTo($to)) {
                throw InvalidStatusTransition::between($from, $to);
            }

            if ($from->requiresReason($to) && $reason === null) {
                throw ReasonRequired::for($from, $to);
            }

            $locked->status = $to;
            $locked->save();

            $locked->logs()->create([
                'action' => $from->logActionFor($to),
                'old_value' => $from->value,
                'new_value' => $to->value,
                'user_name' => $actor->name,
                'metadata' => $reason !== null ? ['reason' => $reason] : null,
            ]);

            return $locked;
        });
    }
}
