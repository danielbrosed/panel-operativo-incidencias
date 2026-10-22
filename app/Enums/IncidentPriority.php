<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Same vocabulary as Optima Retail's own "Abrir incidencia" form: green, yellow and red codes.
 */
enum IncidentPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Código verde',
            self::Medium => 'Código amarillo',
            self::High => 'Código rojo',
        };
    }

    public function impact(): string
    {
        return match ($this) {
            self::Low => 'Bajo impacto',
            self::Medium => 'Medio impacto',
            self::High => 'Urgencia',
        };
    }

    /**
     * Response time committed when the incident is opened. Red is Optima Retail's public promise
     * ("en menos de 4 horas"); yellow and green are assumptions for this exercise, documented in the README.
     */
    public function slaHours(): int
    {
        return match ($this) {
            self::Low => 72,
            self::Medium => 24,
            self::High => 4,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $priority): string => $priority->value, self::cases());
    }
}
