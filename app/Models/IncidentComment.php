<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Operational follow-up note. The comment row is its own trace (author and date), so adding one does not
 * write an incident_logs entry: it would only show twice in the history.
 *
 * @property int $id
 * @property int $incident_id
 * @property string $author_name
 * @property string $body
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
class IncidentComment extends Model
{
    protected $fillable = [
        'author_name',
        'body',
    ];

    /** @return BelongsTo<Incident, $this> */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }
}
