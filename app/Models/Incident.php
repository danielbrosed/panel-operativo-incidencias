<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IncidentLogAction;
use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use Database\Factories\IncidentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $title
 * @property string $description
 * @property IncidentStatus $status
 * @property IncidentPriority $priority
 * @property string $requester_name
 * @property string|null $assigned_to
 * @property string|null $store
 * @property CarbonImmutable $due_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
class Incident extends Model
{
    /** @use HasFactory<IncidentFactory> */
    use HasFactory;

    /**
     * `status` and `due_at` are left out on purpose: the status only changes through
     * App\Actions\Incidents\ChangeIncidentStatus, and due_at is fixed when the incident is opened.
     */
    protected $fillable = [
        'title',
        'description',
        'priority',
        'requester_name',
        'assigned_to',
        'store',
    ];

    protected function casts(): array
    {
        return [
            'status' => IncidentStatus::class,
            'priority' => IncidentPriority::class,
            'due_at' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<IncidentLog, $this> */
    public function logs(): HasMany
    {
        return $this->hasMany(IncidentLog::class);
    }

    /** @return HasMany<IncidentComment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(IncidentComment::class);
    }

    public function isOverdue(?CarbonInterface $now = null): bool
    {
        return $this->status !== IncidentStatus::Resolved && $this->due_at->isBefore($now ?? now());
    }

    /**
     * Adds a boolean `flagged_for_review` attribute: true when the diagnostic left a manual-review mark.
     * Derived from the trail instead of a column, so there is a single source of truth.
     *
     * @param  Builder<Incident>  $query
     */
    public function scopeWithReviewFlag(Builder $query): void
    {
        $query->withExists(self::reviewFlag());
    }

    /** Same as the withReviewFlag scope, for an incident that is already loaded. */
    public function loadReviewFlag(): static
    {
        return $this->loadExists(self::reviewFlag());
    }

    /** @return array<string, Closure(Builder<IncidentLog>): void> */
    private static function reviewFlag(): array
    {
        return [
            'logs as flagged_for_review' => function (Builder $logs): void {
                $logs->where('action', IncidentLogAction::FlaggedForReview)->whereNull('voided_at');
            },
        ];
    }
}
