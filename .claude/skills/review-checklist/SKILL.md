---
name: review-checklist
description: Úsala antes de dar por terminada una tarea, de hacer commit o de revisar código ajeno en este proyecto. Lista de comprobación de integridad de datos, errores, seguridad, frontend y cumplimiento del enunciado de la prueba técnica.
---

# Revisión antes de commit

Marca cada punto mirando el diff, no de memoria. Si algo no aplica, dilo.

## Datos y dominio

- [ ] Toda escritura que toca la incidencia y su trazabilidad va en `DB::transaction()` con `lockForUpdate()`.
- [ ] Ningún estado, prioridad o acción escrito como string suelto fuera de `app/Enums/` y de las migraciones.
- [ ] `incident_logs`: ni `update` ni `delete` (solo `void()`); fechas y autores reales.
- [ ] `$fillable` explícito; `status` fuera.
- [ ] Sin N+1: relaciones con `with()` / `withExists()`; `Model::shouldBeStrict()` lo detecta en desarrollo.
- [ ] Búsquedas `LIKE` escapando `%`, `_` y `\` (`App\Support\Like`).

## Errores

- [ ] Cada error de dominio llega al formulario correcto (`errorBag`) y en español.
- [ ] Ningún 409 a Inertia; 419 y 429 vuelven atrás con un aviso legible.
- [ ] Los filtros GET con valores raros se ignoran, no rompen la página.

## Frontend

- [ ] El estado de los filtros vive en la URL; las acciones redirigen y Inertia refresca las props.
- [ ] El frontend no decide transiciones: usa `incident.transitions` del servidor.
- [ ] Componentes pequeños, sin CSS suelto (tokens del `@theme` de `resources/css/app.css`).
- [ ] Accesible: etiquetas, foco visible, diálogos con teclado, estado no transmitido solo por color.
- [ ] Sin desbordamiento horizontal a 375 px; sin errores ni violaciones de CSP en consola.
- [ ] Nada de `dangerouslySetInnerHTML`.

## Enunciado de la prueba

- [ ] Lo que cambia sigue cumpliendo los ejercicios 1-5 y el README mínimo (sección 10 del PDF).
- [ ] Si cambia un comando, una consulta o una salida, el README se actualiza con la salida real.

## Cierre

- [ ] `composer check` en verde.
- [ ] Commit en inglés, imperativo, sin líneas de coautoría.
