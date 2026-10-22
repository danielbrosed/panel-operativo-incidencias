<?php

declare(strict_types=1);

namespace App\Queries;

use App\Data\IncidentFilters;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Support\Like;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class IncidentListQuery
{
    /**
     * The page links carry the filters as they were understood, not whatever the query string brought.
     *
     * @return LengthAwarePaginator<int, Incident>
     */
    public function paginate(IncidentFilters $filters, int $perPage): LengthAwarePaginator
    {
        return $this->filtered($filters)
            ->withReviewFlag()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->appends(array_filter($filters->toArray(), fn (?string $value): bool => $value !== null));
    }

    /**
     * One GROUP BY query. Every status is present in the result, with 0 when there are none.
     *
     * @return array<string, int> keyed by status value
     */
    public function countsByStatus(IncidentFilters $filters): array
    {
        $totals = $this->filtered($filters->withoutStatus())
            ->toBase()
            ->select('status')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = [];
        foreach (IncidentStatus::cases() as $status) {
            $counts[$status->value] = (int) ($totals[$status->value] ?? 0);
        }

        return $counts;
    }

    /** @return Builder<Incident> */
    private function filtered(IncidentFilters $filters): Builder
    {
        return Incident::query()
            ->when($filters->status, fn (Builder $query, IncidentStatus $status) => $query->where('status', $status))
            ->when($filters->priority, fn (Builder $query, $priority) => $query->where('priority', $priority))
            ->when($filters->search, function (Builder $query, string $search): void {
                // A leading-wildcard LIKE cannot use an index; fine for an operations panel of this size.
                // At scale this becomes a FULLTEXT index (or a search engine), see README.
                $pattern = Like::contains($search);
                $query->where(fn (Builder $text) => $text
                    ->where('title', 'like', $pattern)
                    ->orWhere('description', 'like', $pattern)
                    ->orWhere('store', 'like', $pattern)
                    ->orWhere('requester_name', 'like', $pattern)
                    ->orWhere('assigned_to', 'like', $pattern));
            });
    }
}
