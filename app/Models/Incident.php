<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use Carbon\CarbonImmutable;
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

    /** Derived from the trail instead of a column, so the badge has a single source of truth. */
    private const REVIEW_FLAG = 'activeReviewFlags as flagged_for_review';

    /** Not `status` (only ChangeIncidentStatus changes it) nor `due_at` (fixed when the incident is opened). */
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

    /**
     * Manual-review flags the diagnostic left and nobody has reviewed yet.
     *
     * @return HasMany<IncidentLog, $this>
     */
    public function activeReviewFlags(): HasMany
    {
        return $this->logs()->reviewFlags()->active();
    }

    /** @return HasMany<IncidentComment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(IncidentComment::class);
    }

    public function isOverdue(): bool
    {
        return $this->status !== IncidentStatus::Resolved && $this->due_at->isBefore(now());
    }

    /**
     * Adds a boolean `flagged_for_review` attribute: true while an active review flag exists.
     *
     * @param  Builder<Incident>  $query
     */
    public function scopeWithReviewFlag(Builder $query): void
    {
        $query->withExists(self::REVIEW_FLAG);
    }

    /** Same as the withReviewFlag scope, for an incident that is already loaded. */
    public function loadReviewFlag(): static
    {
        return $this->loadExists(self::REVIEW_FLAG);
    }
}
