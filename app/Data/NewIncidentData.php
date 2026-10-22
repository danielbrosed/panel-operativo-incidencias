<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\IncidentPriority;

final readonly class NewIncidentData
{
    public function __construct(
        public string $title,
        public string $description,
        public IncidentPriority $priority,
        public string $requesterName,
        public ?string $assignedTo = null,
        public ?string $store = null,
    ) {}
}
