<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Incidents\ChangeIncidentStatus;
use App\Http\Requests\ChangeIncidentStatusRequest;
use App\Models\Incident;
use App\Support\CurrentActor;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class IncidentStatusController extends Controller
{
    /**
     * Domain errors (illegal transition, stale status, missing reason) are thrown by the action and turned into
     * validation errors in bootstrap/app.php, so they come back to the same dialog like any other error.
     */
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

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => sprintf('Incidencia #%d: %s.', $updated->id, mb_strtolower($updated->status->label())),
        ]);

        return back();
    }
}
