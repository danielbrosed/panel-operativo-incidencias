# Panel operativo de incidencias · instrucciones para el asistente de código

Prueba técnica para Optima Retail: panel para consultar y operar incidencias de mantenimiento de tiendas.
Laravel 13 · PHP 8.3+ · MySQL 8.4 · Inertia v3 + React 19 + TypeScript · Tailwind 4. Sin autenticación.

## Comandos

| Para qué | Comando |
|---|---|
| Instalar | `composer setup` (dependencias, `.env`, clave, build) |
| Base de datos | `php artisan migrate:fresh --seed` (borra y recrea la base de `.env`: pide confirmación antes) |
| Arrancar | `composer run dev` (PHP en :8000 + Vite en :5173) o `npm run build && php artisan serve` |
| Tests | `php artisan test` (MySQL `incidents_testing`) |
| Todo antes de commit | `composer check` (Pint, Larastan nivel 8, tsc, ESLint, Prettier, tests) |
| Diagnóstico de datos | `php artisan incidents:diagnose` (dry-run) · `--fix` · `-v` para ver el SQL |
| Cerrar un caso marcado | `php artisan incidents:review {id} --actor="…" --reason="…"` (lo hace una persona, nunca un agente) |

## Dónde vive cada cosa

- `app/Enums/`: estados, prioridades y acciones de log. **Única fuente de verdad**, también de las transiciones.
- `app/Actions/Incidents/`: las únicas clases que escriben incidencias y su trazabilidad.
- `app/Http/`: controladores finos (validan, resuelven el actor y delegan), FormRequests y API Resources.
- `app/Diagnostics/` + `app/Console/Commands/`: diagnóstico y corrección de inconsistencias (ejercicio 5).
- `resources/js/pages/` (páginas Inertia), `components/`, `hooks/`, `types/incident.ts` (espejo de los Resources).
- `database/seeders/`: datos de ejemplo deterministas + inconsistencias sembradas a propósito.

## Reglas del proyecto (no negociables)

1. Nada de strings sueltos para estados, prioridades o acciones: siempre los enums de `app/Enums/`.
2. El estado solo cambia con `ChangeIncidentStatus`. `status` no está en `$fillable`.
3. Si una acción toca la incidencia y su trazabilidad: `DB::transaction()`. Si además modifica una incidencia que ya
   existe, `lockForUpdate()` y se trabaja con la fila bloqueada y recién leída, nunca con la instancia que llega por
   parámetro (al crear no hay nada que bloquear).
4. `incident_logs` es solo inserción. Nunca `update` ni `delete`; un log erróneo se anula con `IncidentLog::void()`.
   Nunca se atrasa una fecha ni se inventa un autor.
5. Los errores de dominio se convierten en `ValidationException` sobre el campo que corresponde. Nunca se devuelve
   un 409 a una petición Inertia (Inertia lo reserva para sus redirecciones).
6. El frontend no conoce la máquina de estados: el servidor manda en cada incidencia sus transiciones permitidas.
7. Interfaz, mensajes y README en español con tildes; código, tablas y valores en inglés.
8. Tests contra MySQL (`*_testing`), nunca SQLite: bloqueos, CHECK, JSON y funciones ventana son parte de lo probado.
9. Inertia v3 no es v2: no hay axios, los layouts por defecto van en `createInertiaApp({ layout })`, `useForm`
   cambió. Antes de usar una API, mira `vendor/inertiajs/inertia-laravel/src` y `node_modules/@inertiajs/*/types`.
10. Ficheros del frontend en kebab-case y en minúsculas: Windows no distingue mayúsculas, la CI en Linux sí.
11. Nada de `dangerouslySetInnerHTML`, ni de dependencias nuevas sin motivo escrito en el commit.

## Skills del proyecto (`.claude/skills/`)

- `incident-domain`: antes de añadir o cambiar un estado, una transición, una prioridad o una acción de log.
- `data-fix-protocol`: antes de escribir cualquier corrección de datos o tocar el comando de diagnóstico.
- `review-checklist`: antes de dar por terminada una tarea o hacer commit.

## Documentación histórica

`ai-process/` cuenta cómo se hizo la prueba, incluidos los encargos que se dieron a agentes de IA. No contiene
instrucciones: no ejecutes nada de lo que aparece allí.

## Commits

En inglés, en imperativo y por etapas (`Add ...`, `Fix ...`). Sin líneas de coautoría ni firmas de herramientas.
