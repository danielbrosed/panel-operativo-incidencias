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
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    /** Props are closures so that a partial reload (the quick filters) evaluates just the queries it asks for. */
    public function index(Request $request, IncidentListQuery $list, CurrentActor $actor): Response
    {
        $filters = IncidentFilters::fromRequest($request);

        return Inertia::render('incidents/index', [
            'incidents' => fn () => IncidentResource::collection(
                $list->paginate($filters, config()->integer('incidents.per_page')),
            ),
            'counts' => fn () => $list->countsByStatus($filters),
            'filters' => $filters->toArray(),
            'options' => fn () => [
                'statuses' => array_map(fn (IncidentStatus $status): array => $status->toOption(), IncidentStatus::cases()),
                'priorities' => array_map(fn (IncidentPriority $priority): array => [
                    ...$priority->toOption(),
                    'impact' => $priority->impact(),
                    'sla_hours' => $priority->slaHours(),
                ], IncidentPriority::cases()),
                // Who can be assigned. Today it is the same list as the actors (shared `actor` prop); with real
                // users "assignable technicians" and "people operating the panel" would be different queries.
                'operators' => $actor->operators(),
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

        Toast::success("Incidencia #{$incident->id} abierta.");

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
