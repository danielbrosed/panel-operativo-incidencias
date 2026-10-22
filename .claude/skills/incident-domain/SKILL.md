---
name: incident-domain
description: Úsala antes de añadir o cambiar un estado de incidencia, una transición, una prioridad o una acción de incident_logs. Lista todos los sitios que deben cambiar a la vez para que la máquina de estados, la base de datos, el frontend, el diagnóstico y los tests no se desincronicen.
---

# Cambiar el dominio de incidencias sin romper nada

La máquina de estados vive en `app/Enums/IncidentStatus.php`. Todo lo demás se deriva de ahí o la refleja.
Si cambias una pieza, recorre la lista entera en el mismo commit.

## Añadir o renombrar un estado

1. `app/Enums/IncidentStatus.php`: el caso, `label()`, `allowedTransitions()`, `requiresReason()`,
   `requiresConfirmation()` y `actionLabel()` para cada transición nueva.
2. **Migración nueva** que sustituya el `CHECK` de `incidents.status`. Nunca se edita una migración ya publicada:
   las migraciones son historia y los valores van escritos en ellas a propósito.
3. `resources/js/types/incident.ts` (la unión de valores) y el tono visual en `components/status-badge.tsx`.
4. Contadores: `IncidentListQuery::countsByStatus()` devuelve todos los casos del enum; comprueba que la UI los pinta.
5. Diagnóstico: el SQL de `app/Diagnostics/Checks/` usa los valores del enum; revisa la lista de transiciones legales.
6. Tests: la matriz de `tests/Unit/IncidentStatusTest.php` cubre todos los pares `desde → a`; añade los nuevos.
7. README: la tabla de la máquina de estados.

## Añadir una transición

- Solo en `allowedTransitions()`. El servidor la manda al frontend en cada incidencia (`transitions` del Resource);
  la interfaz no se toca salvo el texto de `actionLabel()`.
- Decide si exige motivo (`requiresReason`) y confirmación (`requiresConfirmation`), y escribe el porqué en el README.
- Si sale de `resolved`, la acción de log debe ser `reopened` (`logActionFor()`): la reapertura siempre es explícita.

## Añadir una acción de log

- Caso nuevo en `app/Enums/IncidentLogAction.php`.
- Si representa un cambio de estado, añádela a `IncidentLogAction::statusTrail()`: el diagnóstico solo mira esas.
- Nunca reutilices `status_changed` para algo que no sea una transición hecha por una persona desde el panel.

## Comprobación final

`composer check` en verde y `php artisan migrate:fresh --seed && php artisan incidents:diagnose` mostrando solo las
inconsistencias sembradas a propósito.
