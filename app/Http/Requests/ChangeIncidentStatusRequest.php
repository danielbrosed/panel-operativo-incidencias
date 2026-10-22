<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\IncidentStatus;
use App\Rules\CleanText;
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
                new CleanText(multiline: true),
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

    /**
     * Runs while the rules are being built, before they have validated anything: crafted input (arrays, objects)
     * must not reach IncidentStatus::tryFrom(), which only accepts strings.
     */
    private function requiresReason(): bool
    {
        $from = $this->input('expected_status');
        $to = $this->input('status');

        if (! is_string($from) || ! is_string($to)) {
            return false;
        }

        $from = IncidentStatus::tryFrom($from);
        $to = IncidentStatus::tryFrom($to);

        return $from !== null && $to !== null && $from->requiresReason($to);
    }
}
