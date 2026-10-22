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
    /**
     * Opens an incident and writes its first trail entry (null → open) in the same transaction:
     * there is never an incident without the log that explains its status.
     */
    public function handle(NewIncidentData $data, Actor $actor): Incident
    {
        return DB::transaction(function () use ($data, $actor): Incident {
            $incident = new Incident([
                'title' => $data->title,
                'description' => $data->description,
                'priority' => $data->priority,
                'requester_name' => $data->requesterName,
                'assigned_to' => $data->assignedTo,
                'store' => $data->store,
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
