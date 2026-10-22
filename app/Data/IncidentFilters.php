<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use Illuminate\Http\Request;

/**
 * Filters of the incident list, read from the query string. Unknown values are ignored instead of failing
 * validation: a stale bookmark or a hand-edited URL still shows the list.
 */
final readonly class IncidentFilters
{
    private const SEARCH_MAX_LENGTH = 100;

    public function __construct(
        public ?IncidentStatus $status = null,
        public ?IncidentPriority $priority = null,
        public ?string $search = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $status = $request->query('status');
        $priority = $request->query('priority');
        $search = $request->query('q');
        $search = is_string($search) ? mb_substr(trim($search), 0, self::SEARCH_MAX_LENGTH) : '';

        return new self(
            status: is_string($status) ? IncidentStatus::tryFrom($status) : null,
            priority: is_string($priority) ? IncidentPriority::tryFrom($priority) : null,
            search: $search !== '' ? $search : null,
        );
    }

    /** Counters per status ignore the status filter: each one shows what clicking it would list. */
    public function withoutStatus(): self
    {
        return new self(null, $this->priority, $this->search);
    }

    /** @return array{status: string|null, priority: string|null, q: string|null} */
    public function toArray(): array
    {
        return [
            'status' => $this->status?->value,
            'priority' => $this->priority?->value,
            'q' => $this->search,
        ];
    }
}
