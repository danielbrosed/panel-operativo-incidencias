<?php

declare(strict_types=1);

namespace App\Actions\Incidents;

use App\Data\Actor;
use App\Data\NewIncidentData;
use App\Enums\IncidentLogAction;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use Illuminate\Support\Facades\DB;

final class CreateIncident
{
    /** The incident and its first trail entry (null → open) in one transaction: never one without the other. */
    public function handle(NewIncidentData $data, Actor $actor): Incident
    {
        return DB::transaction(function () use ($data, $actor): Incident {
            // Actions trim the free text they store: seeders and commands call them without the TrimStrings middleware.
            $incident = new Incident([
                'title' => trim($data->title),
                'description' => trim($data->description),
                'priority' => $data->priority,
                'requester_name' => trim($data->requesterName),
                'assigned_to' => $data->assignedTo,
                'store' => filled($data->store) ? trim($data->store) : null,
            ]);
            $incident->status = IncidentStatus::Open;
            $incident->due_at = now()->addHours($data->priority->slaHours())->toImmutable();
            $incident->save();

            $incident->logs()->create([
                'action' => IncidentLogAction::Created,
                'old_value' => null,
                'new_value' => IncidentStatus::Open->value,
                'user_name' => $actor->name,
                'metadata' => ['priority' => $data->priority->value],
            ]);

            return $incident;
        });
    }
}
