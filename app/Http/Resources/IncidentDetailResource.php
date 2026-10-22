<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Incident;
use Illuminate\Http\Request;

/**
 * Mirrored by the `IncidentDetail` type in resources/js/types/incident.ts.
 *
 * @mixin Incident
 */
class IncidentDetailResource extends IncidentResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $incident = parent::toArray($request);

        return [
            ...$incident,
            'description' => $this->description,
            'links' => [
                ...$incident['links'],
                'comments' => route('incidents.comments.store', $this->id),
            ],
        ];
    }
}
