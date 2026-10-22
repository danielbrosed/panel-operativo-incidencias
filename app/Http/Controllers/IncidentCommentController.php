<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Incidents\AddIncidentComment;
use App\Http\Requests\StoreIncidentCommentRequest;
use App\Models\Incident;
use App\Support\CurrentActor;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class IncidentCommentController extends Controller
{
    public function store(
        StoreIncidentCommentRequest $request,
        Incident $incident,
        AddIncidentComment $addComment,
        CurrentActor $actor,
    ): RedirectResponse {
        $addComment->handle($incident, $request->body(), $actor->get());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Comentario añadido.']);

        return back();
    }
}
