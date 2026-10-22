<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\CurrentActor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Who acts from now on: one of the configured operators. */
class SwitchActorRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', Rule::in(CurrentActor::operatorsFromConfig())],
        ];
    }

    public function actorName(): string
    {
        return $this->string('name')->toString();
    }
}
