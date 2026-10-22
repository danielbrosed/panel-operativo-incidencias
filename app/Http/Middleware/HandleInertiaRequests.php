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
            'actor' => function (): array {
                $actor = app(CurrentActor::class);

                return [
                    'current' => $actor->get()->name,
                    'options' => $actor->options(),
                    'update_url' => route('actor.update'),
                ];
            },
        ];
    }
}
