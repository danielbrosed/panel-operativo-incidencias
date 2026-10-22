<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IncidentLogAction;
use App\Exceptions\Incidents\ImmutableIncidentLog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only audit trail of an incident.
 *
 * Through Eloquent a row can never be updated or deleted; the single exception is voiding it once, with a
 * reason, which is how a duplicated entry is neutralised without losing the evidence. Query-builder writes
 * bypass these guards, which is why production should also restrict the database user (see README) and why
 * `incidents:diagnose` exists.
 *
 * @property int $id
 * @property int $incident_id
 * @property IncidentLogAction $action
 * @property string|null $old_value
 * @property string|null $new_value
 * @property string $user_name
 * @property array<string, mixed>|null $metadata
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $voided_at
 * @property string|null $voided_reason
 */
class IncidentLog extends Model
{
    public const UPDATED_AT = null;

    private const VOID_COLUMNS = ['voided_at', 'voided_reason'];

    protected $fillable = [
        'action',
        'old_value',
        'new_value',
        'user_name',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'action' => IncidentLogAction::class,
            'metadata' => 'array',
            'voided_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (IncidentLog $log): void {
            $onlyVoidColumns = array_diff(array_keys($log->getDirty()), self::VOID_COLUMNS) === [];

            if (! $onlyVoidColumns || $log->getOriginal('voided_at') !== null) {
                throw ImmutableIncidentLog::cannotUpdate($log);
            }
        });

        static::deleting(function (IncidentLog $log): void {
            throw ImmutableIncidentLog::cannotDelete($log);
        });
    }

    /** @return BelongsTo<Incident, $this> */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function void(string $reason): void
    {
        $this->forceFill(['voided_at' => now(), 'voided_reason' => $reason])->save();
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    /** @param  Builder<IncidentLog>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('voided_at');
    }
}
