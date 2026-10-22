<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\IncidentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shape of the request only. The business rules (legal transition, reason, stale status) are enforced again
 * by ChangeIncidentStatus under the row lock, where they are authoritative; the reason check here just gives
 * an early, field-level error.
 */
class ChangeIncidentStatusRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(IncidentStatus::class)],
            'expected_status' => ['required', Rule::enum(IncidentStatus::class)],
            'reason' => [
                'nullable',
                'string',
                'max:500',
                Rule::requiredIf(fn (): bool => $this->requiresReason()),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'reason.required' => 'Indica el motivo: queda en el historial de la incidencia.',
        ];
    }

    public function targetStatus(): IncidentStatus
    {
        return IncidentStatus::from($this->string('status')->toString());
    }

    public function expectedStatus(): IncidentStatus
    {
        return IncidentStatus::from($this->string('expected_status')->toString());
    }

    public function reason(): ?string
    {
        return $this->filled('reason') ? $this->string('reason')->trim()->toString() : null;
    }

    private function requiresReason(): bool
    {
        $from = $this->enum('expected_status', IncidentStatus::class);
        $to = $this->enum('status', IncidentStatus::class);

        return $from !== null && $to !== null && $from->requiresReason($to);
    }
}
