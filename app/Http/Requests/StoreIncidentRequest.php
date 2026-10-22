<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\NewIncidentData;
use App\Enums\IncidentPriority;
use App\Rules\ValidUtf8;
use App\Support\CurrentActor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', new ValidUtf8, 'min:5', 'max:160'],
            'description' => ['required', 'string', new ValidUtf8, 'min:10', 'max:5000'],
            'priority' => ['required', Rule::enum(IncidentPriority::class)],
            'requester_name' => ['required', 'string', new ValidUtf8, 'min:2', 'max:120'],
            'assigned_to' => ['nullable', 'string', Rule::in(CurrentActor::operatorsFromConfig())],
            'store' => ['nullable', 'string', new ValidUtf8, 'max:150'],
        ];
    }

    public function toData(): NewIncidentData
    {
        return new NewIncidentData(
            title: $this->string('title')->trim()->toString(),
            description: $this->string('description')->trim()->toString(),
            priority: IncidentPriority::from($this->string('priority')->toString()),
            requesterName: $this->string('requester_name')->trim()->toString(),
            assignedTo: $this->filled('assigned_to') ? $this->string('assigned_to')->toString() : null,
            store: $this->filled('store') ? $this->string('store')->trim()->toString() : null,
        );
    }
}
