<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

/** The `{value, label}` pair the frontend receives for a backed enum case. */
trait HasOption
{
    abstract public function label(): string;

    /** @return array{value: string, label: string} */
    public function toOption(): array
    {
        return ['value' => $this->value, 'label' => $this->label()];
    }
}
