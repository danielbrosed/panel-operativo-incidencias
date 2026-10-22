<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\CleanText;
use Illuminate\Foundation\Http\FormRequest;

class StoreIncidentCommentRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', new CleanText(multiline: true), 'min:2', 'max:2000'],
        ];
    }

    public function body(): string
    {
        return $this->string('body')->toString();
    }
}
