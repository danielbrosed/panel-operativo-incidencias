<?php

declare(strict_types=1);

namespace App\Actions\Incidents;

use App\Data\Actor;
use App\Models\Incident;
use App\Models\IncidentComment;

final class AddIncidentComment
{
    /** A single insert that does not touch the incident or its trail: no transaction needed. */
    public function handle(Incident $incident, string $body, Actor $actor): IncidentComment
    {
        return $incident->comments()->create([
            'author_name' => $actor->name,
            'body' => trim($body),
        ]);
    }
}
