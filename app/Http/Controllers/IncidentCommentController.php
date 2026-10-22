<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Incidents\AddIncidentComment;
use App\Http\Requests\StoreIncidentCommentRequest;
use App\Models\Incident;
use App\Support\CurrentActor;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;

class IncidentCommentController extends Controller
{
    public function store(
        StoreIncidentCommentRequest $request,
        Incident $incident,
        AddIncidentComment $addComment,
        CurrentActor $actor,
    ): RedirectResponse {
        $addComment->handle($incident, $request->body(), $actor->get());

        Toast::success('Comentario añadido.');

        return back();
    }
}
