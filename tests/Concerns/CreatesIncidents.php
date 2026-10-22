<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Actions\Incidents\CreateIncident;
use App\Data\Actor;
use App\Data\NewIncidentData;
use App\Enums\IncidentPriority;
use App\Models\Incident;

trait CreatesIncidents
{
    /** An incident opened through CreateIncident, so it has its first trail entry. */
    private function openIncident(
        string $title = 'Puerta automática no abre',
        string $description = 'No detecta a los clientes en la entrada principal.',
        string $requesterName = 'Óscar Prieto',
        IncidentPriority $priority = IncidentPriority::High,
        string $actor = 'Marta Ruiz',
    ): Incident {
        return app(CreateIncident::class)->handle(new NewIncidentData(
            title: $title,
            description: $description,
            priority: $priority,
            requesterName: $requesterName,
        ), new Actor($actor));
    }

    /** A seeded incident, found by its title. */
    private function incidentTitled(string $title): Incident
    {
        return Incident::query()->where('title', $title)->sole();
    }
}
