# Arreglos de backend de las dos revisiones, en una copia de trabajo aparte

- **Fase:** Última ronda
- **Tipo de agente:** general-purpose

Encargo literal (las rutas de la máquina local están sustituidas por marcadores entre `< >`):

````text
Trabajas en el backend de una prueba técnica en Laravel 13 + MySQL 8.4: un panel de incidencias. Trabajas en una copia de trabajo aparte, en `<copia-de-trabajo>` (rama `professional-pass`). No toques `<repo>`.

Antes de empezar, lee `<copia-de-trabajo>\CLAUDE.md` y las tres skills de `<copia-de-trabajo>\.claude\skills\` (incident-domain, data-fix-protocol y review-checklist). Fuera de su carpeta no se cargan solas, y son las reglas del proyecto.

Hay un revisor de calidad que ha pasado por el código. Tu encargo es aplicar sus hallazgos de backend sin quitar ninguna garantía: transacciones, bloqueo de fila, estado esperado, CHECK, log solo de inserción, dry-run y salidas 0/1 del diagnóstico. A la vez, otro agente trabaja el frontend en `resources/js`.

**Tus ficheros:**
- `app/`, `bootstrap/`, `config/`, `database/`, `routes/`, `lang/`, `tests/` y `phpstan.neon`.

**No toques:**
- `resources/`, `README.md`, `CLAUDE.md`, `.claude/`, `docs/`, `ai-process/`, `.github/` ni `.env.example`. La documentación la actualizo yo con lo que me cuentes.

**Contrato con el frontend.** Son los únicos cambios de JSON permitidos:
- En cada elemento de `transitions` (IncidentResource) se añade la clave `destructive: bool`, que es `true` cuando el destino es «bloqueada». Sale de un método del enum `IncidentStatus`, no de un string.
- Se quitan las props que nadie lee:
  - `updated_at` de IncidentDetailResource;
  - `old_value`, `new_value` y `voided.at` de TimelineEntryResource.

  Antes de quitarlas, confirma con grep en `resources/js` que nadie las lee.
- Se crea `lang/es/pagination.php` con `previous` => 'Anterior' y `next` => 'Siguiente', en texto plano y sin entidades HTML. El frontend pintará `link.label` tal cual.
- Ninguna otra clave de props, resources ni rutas cambia de nombre.

**Hallazgos que aplicar**

1. **Diagnóstico (`app/Diagnostics`).**
   - Fusiona la interfaz `ConsistencyCheck`, que solo tiene un implementador, en la clase abstracta `SqlCheck`, y borra el fichero.
   - Haz que `IncidentDiagnostics::findings()` y su vecindad trabajen con `PlannedAction`, no con tuplas `array{ConsistencyCheck, Finding}` (`$found[0]`, `$pair[1]`).
   - La regla de corrección vive una sola vez, en la constante `RULE`. El docblock remite a ella.
   - Renombra `PlannedAction::isInReview()` a `isFlagPending()` y `isReviewed()` a `isFlagReviewed()`, y `Finding::$check` (string) a `$checkKey`.
   - En `DiagnoseIncidents`, deja de filtrar el plan siete veces con `array_filter`: usa `countBy` o un objeto pequeño.
   - El resumen de `--fix` ya no dice «aplicadas N» contando las marcas como correcciones. Pasa a «Resultado: corregidas X, marcadas para revisión Y, omitidas Z.», y cada fix da su propio mensaje con algo como `appliedMessage()`, sin parchear el texto de `FixOutcome::Applied` desde el comando.
   - Que las cadenas SQL no lleven `"\n    AND"` metido a mano si se puede escribir más limpio. El SQL que se imprime con `-v` tiene que seguir siendo legible y el mismo que se ejecuta.

2. **`incidents:review`.**
   - La lógica de `ReviewIncident::review()` (escribir el log humano `status_reconciled` y anular las marcas) se va a una acción nueva: `App\Actions\Incidents\ReviewFlaggedIncident::handle(Incident $incident, Actor $actor, string $reason): ReviewOutcome`.
     - `ReviewOutcome` es un DTO readonly con lo que el comando necesita imprimir: el paso conciliado o por qué no hay, y las marcas anuladas.
     - Va en transacción y con `lockForUpdate`, como las otras acciones.
   - El comando valida con `Validator::make()`:
     - `exists:incidents,id`;
     - `Rule::in()` sobre la lista de operadores;
     - `new CleanText` y `max` en el motivo.

     Deja de validar a mano y de devolver tuplas.
   - La cadena «Revisada por {actor}: …» sale de un único método.
   - Así vuelve a ser verdad lo que dicen `CLAUDE.md` y el README: «toda escritura pasa por una acción», con el diagnóstico escribiendo desde `app/Diagnostics/Fixes`.

3. **Comentarios que no son verdad. Corrígelos:**
   - `ConsistencyCheck.php:17` dice que el SQL está «copied into the README»: no lo está. Lo cierto es que se imprime con `-v`.
   - `InconsistenciesSeeder.php:18` dice «each one in its own check», pero C y D comparten la comprobación 3.
   - `phpstan.neon:9` dice que el resto de config es esqueleto «untouched», pero app, database, session, cache, queue y filesystems tienen cambios. Que diga la verdad, y si es razonable, analiza también esos ficheros.

4. **Rastros del proceso dentro del código:**
   - En la migración `2026_10_22_000001_create_incidents_table.php:41`, cambia «(see .claude/skills/incident-domain)» por la instrucción técnica en sí: añadir una migración que reemplace el CHECK.
   - En `config/security.php:11`, cambia «so a reviewer never gets a blank page» por «so local development never shows a blank page».
   - Recorta la prosa justificativa: «on purpose» sale unas 15 veces. Y los docblocks largos, como el de `ReviewIncident.php:19-30`: que se queden en una o dos líneas que expliquen el porqué, no el qué.
   - En `IncidentLogAction.php:20`, el actor del diagnóstico está escrito a mano. Ese valor es de config.

5. **Nombres.**
   - Renombra `CurrentActor::options()` a `operators()`.
   - Revisa la mezcla actor/operador en `SwitchActorRequest`.
   - Las excepciones mezclan sufijos. Renombra la base `IncidentDomainException` a `IncidentRuleViolation`, o pon el sufijo `Exception` en todas, lo que quede más idiomático, y aplícalo en todas.
   - `CurrentActor::isOperator`, `IncidentResource::transitionsFrom` e `IncidentFilters::SEARCH_MAX_LENGTH` pasan a privados si solo se usan dentro de su clase.

6. **El texto se recorta en una sola capa.**
   - El middleware `TrimStrings` recorta la entrada HTTP. Quita los `->trim()` de los FormRequest: `StoreIncidentRequest`, `StoreIncidentCommentRequest` y `ChangeIncidentStatusRequest`.
   - Las acciones son la frontera del dominio y también las llaman seeders y comandos, así que normalizan el texto libre que guardan. Ahora mismo lo hacen unas sí y otras no (`CreateIncident` no recorta): que lo hagan todas igual.
   - Déjalo en una línea de comentario donde corresponda.

7. **Caminos muertos o especulativos.**
   - Quita la respuesta 409 para clientes JSON, que no tiene ningún cliente: `IncidentDomainException`, `StaleIncidentStatus`, `bootstrap/app.php` y su test en `ChangeIncidentStatusTest`. Los errores de dominio siempre son un `ValidationException` sobre su campo (422).
   - Quita el parámetro `$now` que nadie pasa en `Incident::isOverdue()`.
   - Quita el fallback inalcanzable de `Actor::system()`: usa `config()->string('incidents.diagnostics.actor')`.
   - Quita de `lang/es.json` lo que nunca se lanza sin autenticación: «Payment Required», «Unauthorized» y «Forbidden».

8. **Helpers duplicados.**
   - La etiqueta de estado en minúsculas se calcula en 6 sitios: `SqlCheck`, `ReviewIncident`, `ReconcileStatus`, `InvalidStatusTransition`, `StaleIncidentStatus` e `IncidentStatusController`. Sustitúyela por `IncidentStatus::lowerLabel()` y un helper estático para valores que no son del enum, como los que vienen de la base de datos rota.
   - La regex de caracteres de control y de formato se monta tres veces: `CleanText`, `Console/Commands/Concerns/PrintsUntrustedText` y `ReviewIncident`. Pásala a `App\Support\UntrustedText`, con `containsForbidden()` y `forTerminal()`, y que los tres la usen. Los tests de `CleanText` y de la salida de consola tienen que seguir pasando igual.
   - La forma `['value' => …, 'label' => …]` se repite unas 6 veces: añade `toOption()` a los enums.
   - La consulta de «marcas activas» está escrita tres veces: `Incident.php:98` (con `whereNull('voided_at')` en vez del scope `active()`), `FlagForReview` y `ReviewIncident`. Que quede en un solo sitio, con un scope o una relación.
   - `Inertia::flash('toast', [...])` se repite cinco veces: haz un helper pequeño.

9. **Detalles de backend.**
   - En `bootstrap/app.php`, usa `$request->inertia()` y no `header('X-Inertia')`.
   - Usa siempre los getters con tipo de config: `config()->integer()`, `->array()` y `->string()`.
   - Pon `Route::pattern('incident', '[0-9]+')` en vez de tres `whereNumber`.
   - En `HandleInertiaRequests`, que deje de usar `app()` como localizador si se puede inyectar o resolver de forma más limpia.
   - Quita de las migraciones los guards `DB::getDriverName() === 'mysql'`: MySQL es obligatorio y los tests se niegan a correr en otra cosa.
   - En el seeder de demo, cambia las tuplas posicionales (`[horas, estado, actor, motivo]`) por arrays con clave, sin cambiar los datos.
   - En `AppServiceProvider::register()`, el forzado de `inertia.devtools.enabled` es más convencional como bloque `devtools` completo en `config/inertia.php`.
     - Lee primero el config del paquete en `vendor/inertiajs/inertia-laravel/config/inertia.php`: `mergeConfigFrom` es superficial, así que si declaras `devtools` tiene que llevar todas sus subclaves.
     - Que las DevTools sigan apagadas salvo que `INERTIA_DEVTOOLS_ENABLED=true`, también con `APP_ENV=local`. Si no hay un test que lo cubra, añádelo.

10. **Tests.**
    - El helper que crea una incidencia está copiado en `ChangeIncidentStatusTest`, `CommentsAndTimelineTest`, `IntegrityTest` y `SecurityTest`, e `incident(string $title)` en los dos tests de diagnóstico: sácalo a `tests/Concerns/CreatesIncidents`.
    - Las constantes de escenario A, B, C y D, duplicadas en `DiagnoseIncidentsCommandTest` y `ReviewIncidentCommandTest`, pasan a un trait compartido con nombres que digan qué son:
      - `RESOLVED_BY_SQL`
      - `REOPENED_BY_SQL`
      - `DOUBLE_CLICK`
      - `IMPORTED_WITH_GAP`

      Los nombres de test dejan de usar letras: por ejemplo, `test_fix_reconciles_a_voids_the_copy_in_c_and_flags_b_and_d`.
    - La CSP se prueba en `SecurityTest` y en `SecurityHeadersTest`: júntala en uno.
    - Donde el valor depende de config (el 15 por página o la lista de operadores), léelo de `config()`.
    - Afloja los asserts frágiles sobre la salida exacta de consola si hay una forma más robusta de comprobar lo mismo.
    - Añade el caso que falta: una nota opcional al resolver se guarda en `metadata.reason`.

**Cómo verificar** (carga el entorno con `source <tools>/env.sh` en Bash o `. <tools>\env.ps1` en PowerShell):
- Tests:
  - `DB_DATABASE=incidents_wt_testing php artisan test`
  - Usa solo esa base: la del frontend es otra. MySQL ya está arrancado en 127.0.0.1:3306; no lo pares ni lo reinicies.
- `vendor/bin/pint --test` (o `vendor/bin/pint` para formatear) y `vendor/bin/phpstan analyse --memory-limit=1G`, a nivel 8 y sin `@phpstan-ignore` de relleno.
- No corras `npm run lint`, `types` ni `composer check`: el frontend está cambiando a la vez.
- Prueba de humo del diagnóstico sobre la base `incidents_wt`:
  1. `php artisan migrate:fresh --seed --force`.
  2. `php artisan incidents:diagnose`: tiene que salir con 1 y listar los cuatro casos.
  3. `php artisan incidents:diagnose --fix --force`: concilia el caso de la resuelta por SQL, anula la copia del doble clic y marca los otros dos.
  4. `php artisan incidents:diagnose`: sale con 0 y muestra dos casos en revisión manual.
  5. `php artisan incidents:review 17 --actor="Andrés Molina" --reason="Revisado con el técnico"` funciona como antes. Comprueba antes en el seeder qué id tiene el caso de importación.

  Copia la salida literal de los pasos 2, 3 y 4 en tu informe.

**Reglas:**
- No hagas commits ni toques git.
- No pares servidores ni procesos que no hayas arrancado tú.
- Si un hallazgo te parece equivocado o empeora el código, no lo apliques y explica por qué.
- No metas código especulativo nuevo.

**Informe final, corto:**
- qué has aplicado;
- qué no y por qué;
- cada clase o método renombrado, movido o borrado, de forma literal: necesito actualizar la documentación y las skills que los citan;
- las salidas del diagnóstico;
- el recuento final de tests y aserciones.
````

Mensaje 1 que la sesión principal le añadió mientras trabajaba, también literal:

````text
Te sumo al encargo tres hallazgos de una revisión de seguridad que ha terminado ahora, porque caen en ficheros que ya estás tocando. Aplícalos dentro de tu refactor, no como parche aparte.

El diff propuesto y dos tests de prueba están en esta carpeta del scratchpad:
`<scratchpad>\review2-security\`
- `propuesta-fixes.diff`
- `ReviewWaiverProbeTest.php`
- `InvisibleTextProbeTest.php`

Léelos. Los tests conviértelos en tests de verdad, con nombres del proyecto y en el sitio que toque.

1. **R2-01 (media).**
   - El fallo: `FlagForReview::existingFor()` reconoce una marca solo por `check` + `evidence_log_id`. Una marca revisada con `incidents:review` hace que cualquier hallazgo futuro con esa pareja salga como «ya revisado» y el diagnóstico salga con 0, aunque alguien vuelva a cambiar el estado por SQL a otro distinto.
   - El arreglo: la identidad de la marca incluye también el detalle del hallazgo, que lleva el estado. El diff lo hace con `str_starts_with($flag->metadata['detail'], $finding->detail.' ')`. Si al unificar la consulta de marcas activas sale una forma más limpia de guardar esa identidad (por ejemplo, el estado observado en `metadata`), vale, siempre que funcione también con las marcas que ya existen.
   - Test: revisar → segunda manipulación distinta → el diagnóstico vuelve a avisar y sale con 1.
2. **R2-02 (baja).**
   - El fallo: la lista explícita `FORMAT_CHARACTERS` deja pasar 421 invisibles (etiquetas U+E0000–E007F, U+2060, U+00AD, rellenos Hangul, selectores de variación…).
   - El arreglo: en tu nuevo `App\Support\UntrustedText`, usa la clase `\p{Cf}` excepto ZWNJ/ZWJ, más la lista del diff (`(?![\x{200C}\x{200D}])\p{Cf}|[\x{2028}\x{2029}\x{115F}\x{1160}\x{3164}\x{FFA0}\x{E0100}-\x{E01EF}]`).
   - Que los emoji con ZWJ/VS16 y el ZWNJ sigan pasando. Test incluido.
3. **R2-03 (baja).**
   - El fallo: en local, `trustHosts()` está apagado y no hay autenticación, así que hay DNS rebinding.
   - El arreglo: en `AppServiceProvider::boot()`, si `isLocal()`, `Request::setTrustedHosts` con solo loopback (localhost, 127.0.0.1, [::1]) más el host de `APP_URL` con `preg_quote`, para quien use un dominio `.test`.
   - Test: un host ajeno da 400 en GET y en POST, y 127.0.0.1 da 200.

Y tres informativas pequeñas:

4. En `IncidentListQuery`, `withQueryString()` repite en los enlaces de paginación cualquier parámetro y un `q` de 5.000 caracteres. Cámbialo por `appends()` con los filtros ya normalizados.
5. Al volver atrás tras un error (manejador 419/429 de `bootstrap/app.php`), `back()` sigue el Referer sin mirar el host. Si es limpio, redirige a `url()->previousPath()`. Con un test, si cabe.
6. `composer.json` autoriza el plugin `php-http/discovery`, que no está en el lock: quítalo de `allow-plugins` y ejecuta `composer validate --strict`. Es el único cambio permitido en `composer.json`; si hace falta, `composer update --lock`.

Incluye todo esto en tu informe final.
````
