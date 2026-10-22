<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\IncidentStatus;
use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An incident as the list needs it. Mirrored by the `Incident` type in resources/js/types/incident.ts.
 *
 * @mixin Incident
 */
class IncidentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'store' => $this->store,
            'status' => $this->status->toOption(),
            'priority' => [...$this->priority->toOption(), 'impact' => $this->priority->impact()],
            'requester_name' => $this->requester_name,
            'assigned_to' => $this->assigned_to,
            'created_at' => $this->created_at->toIso8601String(),
            'due_at' => $this->due_at->toIso8601String(),
            'is_overdue' => $this->isOverdue(),
            'flagged_for_review' => (bool) $this->getAttribute('flagged_for_review'),
            // The UI never decides which transitions are legal: it renders these.
            'transitions' => self::transitionsFrom($this->status),
            'links' => [
                'self' => route('incidents.show', $this->id),
                'status' => route('incidents.status.update', $this->id),
            ],
        ];
    }

    /**
     * @return list<array{to: array{value: string, label: string}, action_label: string, requires_reason: bool,
     *     requires_confirmation: bool, destructive: bool}>
     */
    private static function transitionsFrom(IncidentStatus $from): array
    {
        return array_map(fn (IncidentStatus $to): array => [
            'to' => $to->toOption(),
            'action_label' => $from->actionLabel($to),
            'requires_reason' => $from->requiresReason($to),
            'requires_confirmation' => $from->requiresConfirmation($to),
            'destructive' => $from->isDestructive($to),
        ], $from->allowedTransitions());
    }
}
