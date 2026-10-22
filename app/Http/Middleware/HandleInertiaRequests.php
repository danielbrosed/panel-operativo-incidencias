<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\CurrentActor;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /**
     * Shared with every page. Mirrored by `sharedPageProps` in resources/js/types/global.d.ts.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            // Inertia resolves prop closures through the container, so the actor is injected only when it is sent.
            'actor' => fn (CurrentActor $actor): array => [
                'current' => $actor->get()->name,
                'options' => $actor->operators(),
                'update_url' => route('actor.update'),
            ],
        ];
    }
}
