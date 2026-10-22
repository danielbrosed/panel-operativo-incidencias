<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\IncidentStatus;
use App\Models\IncidentComment;
use App\Models\IncidentLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One entry of an incident's history: either a trail entry or a comment.
 * Mirrored by the `TimelineEntry` union in resources/js/types/incident.ts.
 *
 * @property IncidentLog|IncidentComment $resource
 */
class TimelineEntryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $entry = $this->resource;

        if ($entry instanceof IncidentComment) {
            return [
                'type' => 'comment',
                'id' => $entry->id,
                'author_name' => $entry->author_name,
                'body' => $entry->body,
                'created_at' => $entry->created_at->toIso8601String(),
            ];
        }

        $metadata = $entry->metadata ?? [];

        return [
            'type' => 'log',
            'id' => $entry->id,
            'action' => ['value' => $entry->action->value, 'label' => $entry->action->label()],
            'old_value' => $entry->old_value,
            'new_value' => $entry->new_value,
            'old_label' => self::statusLabel($entry->old_value),
            'new_label' => self::statusLabel($entry->new_value),
            'user_name' => $entry->user_name,
            'reason' => is_string($metadata['reason'] ?? null) ? $metadata['reason'] : null,
            'detail' => is_string($metadata['detail'] ?? null) ? $metadata['detail'] : null,
            'inferred' => ($metadata['inferred'] ?? false) === true,
            'created_at' => $entry->created_at->toIso8601String(),
            'voided' => $entry->isVoided() ? [
                'at' => $entry->voided_at?->toIso8601String(),
                'reason' => $entry->voided_reason,
            ] : null,
        ];
    }

    private static function statusLabel(?string $value): ?string
    {
        return $value !== null ? IncidentStatus::tryFrom($value)?->label() : null;
    }
}
