<?php

declare(strict_types=1);

// Only the rules this application uses. Anything else falls back to the framework's English messages.
return [

    'enum' => 'El valor de :attribute no es válido.',
    'in' => 'El valor de :attribute no es válido.',
    'max' => [
        'string' => 'El campo :attribute no puede tener más de :max caracteres.',
    ],
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',

    'attributes' => [
        'assigned_to' => 'responsable',
        'body' => 'comentario',
        'description' => 'descripción',
        'expected_status' => 'estado actual',
        'name' => 'operador',
        'priority' => 'prioridad',
        'reason' => 'motivo',
        'requester_name' => 'solicitante',
        'status' => 'estado',
        'store' => 'tienda',
        'title' => 'título',
    ],

];
