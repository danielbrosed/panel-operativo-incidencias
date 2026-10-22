<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Incidents\CreateIncident;
use App\Data\IncidentFilters;
use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Http\Requests\StoreIncidentRequest;
use App\Http\Resources\IncidentDetailResource;
use App\Http\Resources\IncidentResource;
use App\Http\Resources\TimelineEntryResource;
use App\Models\Incident;
use App\Queries\IncidentListQuery;
use App\Queries\IncidentTimelineQuery;
use App\Support\CurrentActor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    /**
     * Props are closures so that a partial reload (the quick filters ask only for incidents, counts and
     * filters) evaluates just the queries it needs.
     */
    public function index(Request $request, IncidentListQuery $list, CurrentActor $actor): Response
    {
        $filters = IncidentFilters::fromRequest($request);

        return Inertia::render('incidents/index', [
            'incidents' => fn () => IncidentResource::collection(
                $list->paginate($filters, (int) config('incidents.per_page', 15)),
            ),
            'counts' => fn () => $list->countsByStatus($filters),
            'filters' => $filters->toArray(),
            'options' => fn () => [
                'statuses' => array_map(
                    fn (IncidentStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                    IncidentStatus::cases(),
                ),
                'priorities' => array_map(fn (IncidentPriority $priority): array => [
                    'value' => $priority->value,
                    'label' => $priority->label(),
                    'impact' => $priority->impact(),
                    'sla_hours' => $priority->slaHours(),
                ], IncidentPriority::cases()),
                'operators' => $actor->options(),
            ],
            'urls' => [
                'index' => route('incidents.index'),
                'store' => route('incidents.store'),
            ],
        ]);
    }

    public function store(StoreIncidentRequest $request, CreateIncident $createIncident, CurrentActor $actor): RedirectResponse
    {
        $incident = $createIncident->handle($request->toData(), $actor->get());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Incidencia #{$incident->id} abierta."]);

        // Back to the unfiltered list, where the new incident is the first row.
        return to_route('incidents.index');
    }

    public function show(Incident $incident, IncidentTimelineQuery $timeline): Response
    {
        $incident->loadReviewFlag();

        return Inertia::render('incidents/show', [
            'incident' => IncidentDetailResource::make($incident),
            'timeline' => TimelineEntryResource::collection($timeline->for($incident)),
            'urls' => [
                'index' => route('incidents.index'),
            ],
        ]);
    }
}
