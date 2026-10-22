# Arreglos de frontend de las dos revisiones, en una copia de trabajo aparte

- **Fase:** Última ronda
- **Tipo de agente:** general-purpose

Encargo literal (las rutas de la máquina local están sustituidas por marcadores entre `< >`):

````text
Trabajas en el frontend de una prueba técnica: un panel de incidencias hecho con Laravel 13 e Inertia v3 + React 19 + TypeScript + Tailwind 4. Trabajas en una copia de trabajo aparte, `<copia-de-trabajo>` (rama `professional-pass`). No toques `<repo>`.

Antes de empezar, lee `<copia-de-trabajo>\CLAUDE.md` y las skills `<copia-de-trabajo>\.claude\skills\review-checklist\SKILL.md` e `incident-domain\SKILL.md`. Son las reglas del proyecto, y fuera de su carpeta no se cargan solas.

El encargo es aplicar los hallazgos de frontend de una revisión de calidad sin cambiar lo que ve ni lo que puede hacer el usuario. A la vez, otro agente trabaja el backend en `app/`, `tests/`, etc.

**Tus ficheros:** `resources/js/**`, `resources/css/**`, `eslint.config.js`, `vite.config.ts`, `tsconfig.json` y `package.json` (solo si hace falta), y una línea en `.env.example`. No toques el PHP, `README.md`, `CLAUDE.md`, `.claude/`, `docs/`, `ai-process/` ni `.github/`: la documentación la actualizo yo con lo que me cuentes.

**Contrato con el backend** (los únicos cambios de JSON):
- Cada elemento de `transitions` trae una clave nueva, `destructive: boolean`: es `true` cuando el destino es «bloqueada».
  - El tono `danger` del botón sale de ahí. Ya no se compara con `'blocked'` en `status-actions.tsx:126`.
- Desaparecen tres props que nadie lee. Quítalas de los tipos TS:
  - `updated_at` del detalle;
  - `old_value` y `new_value` de las entradas del historial;
  - `voided.at`.
- La paginación de Laravel traerá las etiquetas «Anterior» y «Siguiente» en texto plano, desde `lang/es/pagination.php`.
  - Pinta `link.label` en todos los enlaces y quita la sustitución a mano de `pagination.tsx:28-29` y su comentario.
- Nada más cambia de nombre en el JSON.

**Hallazgos que aplicar**

1. **`StatusActions` mezcla demasiado estado.**
   - El problema: cada fila se pinta dos veces (tabla de escritorio y tarjetas de móvil), así que con 15 filas hay 30 `useForm`, 30 `usePage` y 30 diálogos en portal. Para sostenerlo existen las refs `sentDirectly` y `shownStatus` y `keepFocus` (`status-actions.tsx:36-67`), más el baile de portal y montaje de `confirm-dialog.tsx:27-42`.
   - El arreglo:
     - Sube el cambio de estado a la página con un hook `useStatusChange()`: un solo formulario y un solo `<ConfirmDialog>` por página.
     - `StatusActions` se queda en «botones + `onChoose(incident, transition)`».
     - Úsalo en las dos páginas: `pages/incidents/index.tsx` y `show.tsx`.
   - Tiene que seguir funcionando igual todo esto (léelo en el código actual antes de tocarlo):
     - Las transiciones sin confirmación se envían directas.
     - Las que llevan confirmación abren el diálogo, con campo de motivo cuando `requires_reason`.
     - Un error de validación sale en su campo, y el foco va al primer error.
     - **Estado obsoleto**: si otro operador cambió la incidencia, el diálogo lo dice y los botones se recalculan con las props nuevas.
     - El foco vuelve al botón que abrió el diálogo, o al fallback de `use-focus-fallback` si ese botón ya no existe.
     - Los errores 419 y 429 no pierden lo escrito: el backend devuelve un error `request` y un aviso.
     - Bloqueo de doble envío mientras procesa.
     - Escape y cancelar cierran el diálogo sin enviar.
     - Funciona con teclado.
   - Si con un solo diálogo ya no hace falta el portal, quítalo, pero comprueba que el `<dialog>` modal sigue funcionando dentro del layout.

2. **Coherencia de APIs.**
   - `FilterBar` recibe el objeto entero del hook (`state`, `filter-bar.tsx:9`); `StatusCounters` recibe props sueltas. Que los dos reciban props sueltas.
   - En `confirm-dialog.tsx` se mezclan props desestructuradas con `props.x`: desestructura todo.

3. **Duplicados.**
   - `Detail` (`show.tsx:92-99`) y `CardDetail` (`incident-list.tsx:107-114`) son iguales: déjalos en un único `components/ui/detail.tsx`.
   - `show.tsx:56-58` reimplementa «Sin asignar» en vez de usar `<Assignee>` (`incident-cells.tsx:37-39`).
   - `DateWithRelative` (`show.tsx:105-111`) y `OpenedAt` (`incident-cells.tsx:28-35`) hacen lo mismo: unifícalos.
   - La clase de rótulo `text-xs font-semibold tracking-wide text-muted uppercase` sale tres veces (`incident-list.tsx:36`, `timeline.tsx:34,61`): conviértela en `@utility eyebrow` en el CSS o en un componente.
   - El h1 se repite (`index.tsx:32`, `show.tsx:34`): saca un `PageTitle`.
   - El enlace de volver (`show.tsx:26`) y `HistoryLink` (`incident-cells.tsx:45`) rehacen la variante `link` del botón: usa `buttonClasses('link', …)` o el equivalente que exista.
   - Renombra `incident-cells.tsx` a `incident-row-parts.tsx`: contiene funciones de clase y partes de fila, no celdas.

4. **Nombres.** Elige el singular en todo: `IncidentIndex` (hoy conviven `IncidentsIndex` en `index.tsx:18` e `IncidentIndexProps` en `types/incident.ts`) y `IncidentShow`.

5. **Muertos y restos.**
   - `formatRelative(…, now)` (`format.ts:19`) tiene un parámetro `now` que nadie pasa.
   - `'sin motivo indicado'` (`timeline.tsx:51`) no puede salir nunca, porque la base de datos obliga a que haya motivo: tipa `reason: string`.
   - Nadie importa el export `ControlProps` (`field.tsx:5`).
   - `eslint.config.js:7` ignora `bootstrap/ssr/**`, que no existe.
   - `tseslint.config` está marcado `@deprecated` en la versión instalada: pásalo a `defineConfig` de `eslint/config`. Comprueba en `node_modules` que la API existe en las versiones instaladas.

6. **Valores a mano.**
   - `pr-11 pl-11` (`filter-bar.tsx:37`) se queda en `px-11`.
   - `-left-[7px]` (`timeline.tsx:15`) pasa a `-left-1.75`, si Tailwind 4.3 lo genera igual.
   - El color de marca está escrito a mano en `app.tsx:21` (`'#0086FF'`). La barra de progreso de Inertia inyecta CSS, así que debería admitir `var(--color-primary)`; compruébalo en su código, en `node_modules/@inertiajs`. Si no se puede, déjalo en una sola constante con una línea que diga qué token copia.
   - El 150 de `app.tsx:21` y el `delay-150` de `index.tsx:63` son el mismo número acoplado: que vivan juntos o que se diga.
   - El nombre de la app está escrito en tres sitios: `app.tsx:4`, `app-layout.tsx:17` y `APP_NAME`. Usa `VITE_APP_NAME="${APP_NAME}"`, añadido a `.env.example` justo debajo de `APP_NAME`, con un único fallback en un sitio.

7. **Comentarios.**
   - Recorta los docblocks largos a una o dos líneas que expliquen el porqué: `confirm-dialog.tsx:22-30`, `use-focus-fallback.ts:5-11` y los que veas.
   - `show.tsx:17` repite el comentario de `index.tsx:24`.
   - Los `maxLength` del frontend copian las reglas PHP; con una línea que lo diga basta.

**Cómo verificar:**
- Comprobaciones de código: `npm run types`, `npm run lint`, `npm run format:check` (o `npx prettier --write` sobre tus ficheros) y `npm run build`. Todo en verde.
- Recorrido real en el navegador con Puppeteer.
  - **Preparar la base.** En PowerShell, `. <tools>\env.ps1`. Luego, en `<copia-de-trabajo>`:
    ```
    <tools>\mysql-8.4\bin\mysql.exe -uroot -h127.0.0.1 -e "CREATE DATABASE IF NOT EXISTS incidents_wt_fe"
    $env:DB_DATABASE='incidents_wt_fe'; php artisan migrate:fresh --seed --force
    ```
  - **Servir la app.** Hazlo en segundo plano y desde `public`:
    ```
    $env:DB_DATABASE='incidents_wt_fe'; $env:CACHE_STORE='array'; php -S 127.0.0.1:8091 ..\vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php
    ```
  - **Ten en cuenta:**
    - El backend cambia a la vez, así que alguna petición puede fallar un momento. Si pasa, vuelve a probar.
    - MySQL ya está arrancado. No lo pares, y tampoco el servidor del puerto 8080.
  - **Herramientas:**
    - Puppeteer está instalado globalmente en `<puppeteer global>`. Cárgalo con `createRequire`.
    - Chrome está en `<Chrome>`.
    - Hay un script de QA anterior que puedes adaptar: `<scratchpad>\qa\page-check.mjs`.
    - Los scripts nuevos van en esa misma carpeta `scratchpad\qa\`, nunca dentro del repo.
  - **Qué recorrer.** Hazlo en escritorio (1440 px) y en 375 px:
    - pasar a revisión directo;
    - bloquear con y sin motivo (el error tiene que salir en su campo);
    - resolver con confirmación;
    - reabrir con motivo;
    - un conflicto de estado: cambia el estado por otra vía, por ejemplo con otra pestaña o con una petición, con el diálogo abierto;
    - Escape;
    - comprobar adónde va el foco al cerrar;
    - la paginación con sus etiquetas;
    - el detalle con su historial.
  - **Criterio de éxito:** la consola del navegador sin errores ni violaciones de CSP, y ningún cambio visual frente a antes. Compara con las capturas de `<copia-de-trabajo>\docs\capturas\`.
  - **Al terminar,** para el servidor que has arrancado y borra la base `incidents_wt_fe`.
- **Si a la hora de tu recorrido final la clave `destructive` todavía no está en `app/Http/Resources/IncidentResource.php`,** haz el recorrido igualmente y dilo en el informe.

**Reglas:**
- Ni commits ni git.
- No pares procesos que no hayas arrancado tú.
- Nada de `dangerouslySetInnerHTML`, estilos inline sin nonce ni dependencias nuevas.
- Si un hallazgo te parece equivocado o empeora el código, no lo apliques y explica por qué.

**Informe final, corto:**
- qué has aplicado;
- qué no has aplicado y por qué;
- los ficheros renombrados, creados o borrados, de forma literal: necesito actualizar la documentación que los cita;
- el resultado del recorrido.
````

Mensaje 1 que la sesión principal le añadió mientras trabajaba, también literal:

````text
Te sumo al encargo dos hallazgos menores de una revisión de seguridad que ha terminado ahora. Caen en ficheros que ya estás tocando:

1. **R2-10.** `checkVisibility()` se usa sin comprobar si existe, en `resources/js/lib/focus.ts:8` y en `status-actions.tsx:57` (o donde acabe con tu refactor). En Safari anterior a 17.4 lanza un TypeError dentro de un efecto y la página queda en blanco.
   - Protégelo con `typeof el.checkVisibility === 'function'`.
   - Si no existe, el fallback razonable es `el.getClientRects().length > 0`.
2. **R2-09.** `lastListUrl()`, en `resources/js/lib/list-url.ts:19`, devuelve el texto guardado y no la URL ya interpretada. Un valor relativo como `incidents?x` acaba en `/incidents/incidents?…`.
   - Devuelve `candidate.pathname + candidate.search` después de la comprobación de origen.
   - Las cargas maliciosas tienen que seguir acabando en `/incidents`: `//evil`, `/\evil`, `javascript:`, `@evil` y un tabulador.

Cuéntalo en tu informe final.
````
