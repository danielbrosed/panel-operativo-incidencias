<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SwitchActorRequest;
use App\Support\CurrentActor;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;

class ActorController extends Controller
{
    public function update(SwitchActorRequest $request, CurrentActor $actor): RedirectResponse
    {
        $actor->switchTo($request->actorName());

        Toast::success("Ahora actúas como {$request->actorName()}.");

        return back();
    }
}
