<?php

declare(strict_types=1);

/*
 * Demo dataset: maintenance incidents of retail stores. Stores, brands and people are fictional.
 *
 * Times are relative to the moment the seeder runs, so "overdue" stays meaningful whenever it is run:
 *  - opened_hours_ago: when the incident was opened;
 *  - steps:    hours after opening, target status, operator and reason (or null);
 *  - comments: hours after opening, operator and text.
 *
 * Three of these incidents are broken afterwards by InconsistenciesSeeder (see README):
 * "Fuga de agua en aseo de clientes", "Persiana metálica de acceso no sube" and "Puerta automática no abre".
 */
return [
    [
        'title' => 'Sin climatización en la sala de ventas',
        'description' => 'La máquina de aire acondicionado no arranca desde primera hora. La tienda está a 31 °C y hay clientes quejándose.',
        'priority' => 'high',
        'requester' => 'Nerea Gil (encargada)',
        'store' => 'Urbana Sport · Tienda 0218 · C. Colón 32, Valencia',
        'assigned_to' => 'Jordi Vidal',
        'opened_by' => 'Marta Ruiz',
        'opened_hours_ago' => 7,
        'steps' => [],
        'comments' => [
            ['hours' => 0.5, 'actor' => 'Marta Ruiz', 'body' => 'La encargada confirma que el cuadro de la máquina no tiene ningún diferencial saltado.'],
        ],
    ],
    [
        'title' => 'Fluorescente parpadeando en los probadores',
        'description' => 'Dos tubos del probador 3 parpadean de forma continua. No hay riesgo, pero molesta a los clientes.',
        'priority' => 'medium',
        'requester' => 'Iván Soler (dependiente)',
        'store' => 'Nord Studio · Tienda 0107 · C. Fuencarral 88, Madrid',
        'assigned_to' => null,
        'opened_by' => 'Lucía Romero',
        'opened_hours_ago' => 3,
        'steps' => [],
        'comments' => [],
    ],
    [
        'title' => 'Grafiti en la persiana exterior',
        'description' => 'Han pintado la persiana metálica durante la noche. Hay que limpiarla antes del fin de semana.',
        'priority' => 'low',
        'requester' => 'Pilar Ibáñez (encargada)',
        'store' => 'Bambú Home · Tienda 0331 · Paseo Independencia 12, Zaragoza',
        'assigned_to' => 'Andrés Molina',
        'opened_by' => 'Marta Ruiz',
        'opened_hours_ago' => 30,
        'steps' => [],
        'comments' => [],
    ],
    [
        'title' => 'Cambio de filtros de la ventilación pendiente',
        'description' => 'El mantenimiento preventivo trimestral de los filtros de la unidad de ventilación no se hizo en la última visita.',
        'priority' => 'low',
        'requester' => 'Raúl Campos (jefe de zona)',
        'store' => 'Lume Óptica · Tienda 0512 · C. Larios 7, Málaga',
        'assigned_to' => null,
        'opened_by' => 'Andrés Molina',
        'opened_hours_ago' => 80,
        'steps' => [],
        'comments' => [],
    ],
    [
        // Broken by InconsistenciesSeeder (case A).
        'title' => 'Fuga de agua en aseo de clientes',
        'description' => 'Pierde agua la llave de paso del lavabo del aseo de clientes. Se ha cerrado el aseo de forma provisional.',
        'priority' => 'medium',
        'requester' => 'Carmen Vega (encargada)',
        'store' => 'Kalma Beauty · Tienda 0644 · C. Tetuán 19, Sevilla',
        'assigned_to' => 'Lucía Romero',
        'opened_by' => 'Lucía Romero',
        'opened_hours_ago' => 26,
        'steps' => [
            ['hours' => 1, 'to' => 'in_review', 'actor' => 'Lucía Romero', 'reason' => null],
        ],
        'comments' => [
            ['hours' => 1.5, 'actor' => 'Lucía Romero', 'body' => 'Fontanero avisado. Visita prevista mañana a primera hora.'],
        ],
    ],
    [
        'title' => 'Central de alarma de incendios en fallo',
        'description' => 'La central de detección de incendios muestra «fallo de lazo» y pita de forma intermitente.',
        'priority' => 'high',
        'requester' => 'Aitor Etxeberria (encargado)',
        'store' => 'Lume Óptica · Tienda 0405 · Gran Vía 50, Bilbao',
        'assigned_to' => 'Jordi Vidal',
        'opened_by' => 'Marta Ruiz',
        'opened_hours_ago' => 3,
        'steps' => [
            ['hours' => 0.5, 'to' => 'in_review', 'actor' => 'Jordi Vidal', 'reason' => null],
        ],
        'comments' => [
            ['hours' => 0.6, 'actor' => 'Jordi Vidal', 'body' => 'Técnico de PCI en camino. Llegada estimada en 45 minutos.'],
        ],
    ],
    [
        'title' => 'Caja registradora sin corriente en la línea 2',
        'description' => 'El enchufe de la caja 2 no da corriente. El resto de cajas funcionan con normalidad.',
        'priority' => 'medium',
        'requester' => 'Sergio Lara (dependiente)',
        'store' => 'Urbana Sport · Tienda 0222 · C. Marqués de Larios 3, Málaga',
        'assigned_to' => 'Andrés Molina',
        'opened_by' => 'Andrés Molina',
        'opened_hours_ago' => 10,
        'steps' => [
            ['hours' => 2, 'to' => 'in_review', 'actor' => 'Andrés Molina', 'reason' => null],
        ],
        'comments' => [],
    ],
    [
        // A legitimate reopen: it must NOT be reported by the diagnostic.
        'title' => 'Puerta del almacén no cierra bien',
        'description' => 'La puerta cortafuegos del almacén no llega a cerrar sola: el cierrapuertas parece desajustado.',
        'priority' => 'medium',
        'requester' => 'Laia Ferrer (encargada)',
        'store' => 'Nord Studio · Tienda 0101 · Passeig de Gràcia 41, Barcelona',
        'assigned_to' => 'Jordi Vidal',
        'opened_by' => 'Marta Ruiz',
        'opened_hours_ago' => 120,
        'steps' => [
            ['hours' => 3, 'to' => 'in_review', 'actor' => 'Jordi Vidal', 'reason' => null],
            ['hours' => 24, 'to' => 'resolved', 'actor' => 'Jordi Vidal', 'reason' => null],
            ['hours' => 48, 'to' => 'open', 'actor' => 'Marta Ruiz', 'reason' => 'El cierre vuelve a fallar: la tienda avisa de que la puerta se queda entreabierta por la noche.'],
            ['hours' => 50, 'to' => 'in_review', 'actor' => 'Jordi Vidal', 'reason' => null],
        ],
        'comments' => [
            ['hours' => 23, 'actor' => 'Jordi Vidal', 'body' => 'Cierrapuertas ajustado y probado diez veces.'],
            ['hours' => 49, 'actor' => 'Jordi Vidal', 'body' => 'Probablemente haya que sustituir el cierrapuertas entero. Pido presupuesto.'],
        ],
    ],
    [
        // Broken by InconsistenciesSeeder (case C).
        'title' => 'Puerta automática no abre',
        'description' => 'La puerta automática de la entrada principal no detecta a los clientes. Se ha dejado abierta en modo manual.',
        'priority' => 'high',
        'requester' => 'Óscar Prieto (encargado)',
        'store' => 'Bambú Home · Tienda 0302 · C. Serrano 61, Madrid',
        'assigned_to' => 'Andrés Molina',
        'opened_by' => 'Lucía Romero',
        'opened_hours_ago' => 30,
        'steps' => [
            ['hours' => 0.5, 'to' => 'in_review', 'actor' => 'Andrés Molina', 'reason' => null],
            ['hours' => 2, 'to' => 'blocked', 'actor' => 'Andrés Molina', 'reason' => 'Pendiente de pieza: motor del operador de la puerta. El fabricante da un plazo de 48 horas.'],
        ],
        'comments' => [],
    ],
    [
        'title' => 'Goteras en el falso techo tras la lluvia',
        'description' => 'Entra agua por el falso techo junto al escaparate cuando llueve. Hay dos placas manchadas.',
        'priority' => 'medium',
        'requester' => 'Iker Ruiz de Gauna (encargado)',
        'store' => 'Kalma Beauty · Tienda 0610 · C. Ercilla 24, Bilbao',
        'assigned_to' => 'Lucía Romero',
        'opened_by' => 'Lucía Romero',
        'opened_hours_ago' => 96,
        'steps' => [
            ['hours' => 1, 'to' => 'in_review', 'actor' => 'Lucía Romero', 'reason' => null],
            ['hours' => 5, 'to' => 'blocked', 'actor' => 'Lucía Romero', 'reason' => 'Hace falta autorización del centro comercial para subir a la cubierta.'],
        ],
        'comments' => [
            ['hours' => 6, 'actor' => 'Lucía Romero', 'body' => 'Solicitud enviada a la gerencia del centro comercial.'],
        ],
    ],
    [
        // Broken by InconsistenciesSeeder (case B).
        'title' => 'Persiana metálica de acceso no sube',
        'description' => 'La persiana de la entrada se queda a medio subir. La tienda no puede abrir al público.',
        'priority' => 'high',
        'requester' => 'Vicent Martí (encargado)',
        'store' => 'Brisa Kids · Tienda 0703 · C. Don Juan de Austria 6, Valencia',
        'assigned_to' => 'Jordi Vidal',
        'opened_by' => 'Marta Ruiz',
        'opened_hours_ago' => 72,
        'steps' => [
            ['hours' => 0.5, 'to' => 'in_review', 'actor' => 'Jordi Vidal', 'reason' => null],
            ['hours' => 3, 'to' => 'resolved', 'actor' => 'Jordi Vidal', 'reason' => null],
        ],
        'comments' => [
            ['hours' => 2.9, 'actor' => 'Jordi Vidal', 'body' => 'Final de carrera sustituido. La persiana sube y baja sin problema.'],
        ],
    ],
    [
        'title' => 'Plaga de cucarachas en el almacén',
        'description' => 'El personal ha visto cucarachas junto a la zona de cajas vacías del almacén.',
        'priority' => 'medium',
        'requester' => 'Pilar Ibáñez (encargada)',
        'store' => 'Urbana Sport · Tienda 0240 · C. Alfonso I 20, Zaragoza',
        'assigned_to' => 'Andrés Molina',
        'opened_by' => 'Andrés Molina',
        'opened_hours_ago' => 192,
        'steps' => [
            ['hours' => 4, 'to' => 'in_review', 'actor' => 'Andrés Molina', 'reason' => null],
            ['hours' => 48, 'to' => 'resolved', 'actor' => 'Andrés Molina', 'reason' => null],
        ],
        'comments' => [
            ['hours' => 47, 'actor' => 'Andrés Molina', 'body' => 'Tratamiento aplicado. Segunda pasada programada dentro de 15 días.'],
        ],
    ],
    [
        'title' => 'Cerradura de la puerta trasera rota',
        'description' => 'La llave gira en vacío en la puerta de mercancías. La puerta queda sin cerrar con llave.',
        'priority' => 'high',
        'requester' => 'Marina Castro (encargada)',
        'store' => 'Lume Óptica · Tienda 0420 · C. Princesa 15, Madrid',
        'assigned_to' => 'Jordi Vidal',
        'opened_by' => 'Marta Ruiz',
        'opened_hours_ago' => 144,
        'steps' => [
            ['hours' => 1, 'to' => 'in_review', 'actor' => 'Jordi Vidal', 'reason' => null],
            ['hours' => 3, 'to' => 'resolved', 'actor' => 'Jordi Vidal', 'reason' => null],
        ],
        'comments' => [],
    ],
    [
        'title' => 'Rótulo luminoso de la fachada apagado',
        'description' => 'El rótulo de la fachada no se enciende al anochecer. Parece el temporizador.',
        'priority' => 'low',
        'requester' => 'Rocío Navas (encargada)',
        'store' => 'Nord Studio · Tienda 0150 · Av. de la Constitución 9, Sevilla',
        'assigned_to' => 'Lucía Romero',
        'opened_by' => 'Lucía Romero',
        'opened_hours_ago' => 240,
        'steps' => [
            ['hours' => 24, 'to' => 'in_review', 'actor' => 'Lucía Romero', 'reason' => null],
            ['hours' => 72, 'to' => 'resolved', 'actor' => 'Lucía Romero', 'reason' => null],
        ],
        'comments' => [],
    ],
    [
        'title' => 'Cuadro de climatización con error E4',
        'description' => 'El termostato de la sala muestra el error E4 y la máquina se para cada pocos minutos.',
        'priority' => 'medium',
        'requester' => 'Laia Ferrer (encargada)',
        'store' => 'Brisa Kids · Tienda 0711 · C. Pelai 22, Barcelona',
        'assigned_to' => 'Jordi Vidal',
        'opened_by' => 'Marta Ruiz',
        'opened_hours_ago' => 288,
        'steps' => [
            ['hours' => 2, 'to' => 'in_review', 'actor' => 'Jordi Vidal', 'reason' => null],
            ['hours' => 4, 'to' => 'blocked', 'actor' => 'Jordi Vidal', 'reason' => 'El error es del compresor; hay que esperar la autorización del presupuesto por parte de la marca.'],
            ['hours' => 48, 'to' => 'in_review', 'actor' => 'Jordi Vidal', 'reason' => null],
            ['hours' => 51, 'to' => 'resolved', 'actor' => 'Jordi Vidal', 'reason' => null],
        ],
        'comments' => [
            ['hours' => 47, 'actor' => 'Marta Ruiz', 'body' => 'Presupuesto aprobado por la marca.'],
        ],
    ],
    [
        'title' => 'Baldosa suelta en la entrada',
        'description' => 'Hay una baldosa suelta justo en la entrada. Se ha señalizado con un cono.',
        'priority' => 'medium',
        'requester' => 'Hugo Reyes (dependiente)',
        'store' => 'Bambú Home · Tienda 0355 · C. Nueva 4, Málaga',
        'assigned_to' => null,
        'opened_by' => 'Andrés Molina',
        'opened_hours_ago' => 1,
        'steps' => [],
        'comments' => [],
    ],
];
