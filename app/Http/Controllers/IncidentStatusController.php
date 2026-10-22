<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Incidents\ChangeIncidentStatus;
use App\Http\Requests\ChangeIncidentStatusRequest;
use App\Models\Incident;
use App\Support\CurrentActor;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;

class IncidentStatusController extends Controller
{
    /** The action's rule violations come back to the same dialog as validation errors (bootstrap/app.php). */
    public function __invoke(
        ChangeIncidentStatusRequest $request,
        Incident $incident,
        ChangeIncidentStatus $changeStatus,
        CurrentActor $actor,
    ): RedirectResponse {
        $updated = $changeStatus->handle(
            $incident,
            $request->targetStatus(),
            $request->expectedStatus(),
            $actor->get(),
            $request->reason(),
        );

        Toast::success(sprintf('Incidencia #%d: %s.', $updated->id, $updated->status->lowerLabel()));

        return back();
    }
}
