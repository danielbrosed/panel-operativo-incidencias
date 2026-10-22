<?php

declare(strict_types=1);

return [

    /*
     * There is no authentication in this exercise: the person operating the panel picks who they are from this
     * list ("Actuando como…") and App\Support\CurrentActor records that name in every log and comment.
     * A fixed list keeps actor names consistent, which the duplicate-log check relies on.
     * Fictional people.
     */
    'operators' => [
        'Marta Ruiz',
        'Jordi Vidal',
        'Lucía Romero',
        'Andrés Molina',
    ],

    'per_page' => 15,

    'diagnostics' => [
        // Name written in incident_logs.user_name by `php artisan incidents:diagnose --fix`.
        'actor' => 'sistema:diagnostico',

        // Two identical status logs closer than this are the same request processed twice.
        'duplicate_window_seconds' => 2,
    ],

];
