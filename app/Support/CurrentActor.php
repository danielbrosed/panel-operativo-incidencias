<?php

declare(strict_types=1);

namespace App\Support;

use App\Data\Actor;
use Illuminate\Contracts\Session\Session;
use InvalidArgumentException;

/**
 * Who is operating the panel: without authentication, a name picked from config('incidents.operators') and kept in
 * the session. The only place that knows where the actor comes from; with real users it becomes auth()->user().
 */
final class CurrentActor
{
    private const SESSION_KEY = 'actor';

    /** @param  non-empty-list<string>  $operators */
    public function __construct(
        private readonly Session $session,
        private readonly array $operators,
    ) {}

    /** @return non-empty-list<string> */
    public static function operatorsFromConfig(): array
    {
        $operators = array_values(array_filter(
            config()->array('incidents.operators'),
            fn (mixed $name): bool => is_string($name) && $name !== '',
        ));

        if ($operators === []) {
            throw new InvalidArgumentException('config/incidents.php must define at least one operator.');
        }

        return $operators;
    }

    public function get(): Actor
    {
        $name = $this->session->get(self::SESSION_KEY);

        return new Actor(is_string($name) && $this->isOperator($name) ? $name : $this->operators[0]);
    }

    public function switchTo(string $name): void
    {
        if (! $this->isOperator($name)) {
            throw new InvalidArgumentException("Unknown operator: {$name}");
        }

        $this->session->put(self::SESSION_KEY, $name);
    }

    /** @return non-empty-list<string> */
    public function operators(): array
    {
        return $this->operators;
    }

    private function isOperator(string $name): bool
    {
        return in_array($name, $this->operators, true);
    }
}
