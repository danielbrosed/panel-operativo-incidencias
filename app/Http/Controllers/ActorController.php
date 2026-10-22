<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SwitchActorRequest;
use App\Support\CurrentActor;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ActorController extends Controller
{
    public function update(SwitchActorRequest $request, CurrentActor $actor): RedirectResponse
    {
        $actor->switchTo($request->operatorName());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Ahora actúas como {$request->operatorName()}."]);

        return back();
    }
}
