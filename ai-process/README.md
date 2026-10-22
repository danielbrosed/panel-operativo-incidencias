# Uso de IA en esta prueba

La prueba pedía documentar el uso de IA, y en la entrevista se me dijo que interesaba precisamente cómo trabajo con
ella. Por eso el montaje está a la vista en el repositorio: [`CLAUDE.md`](../CLAUDE.md) con las reglas del proyecto y
tres skills en [`.claude/skills/`](../.claude/skills/). Aquí cuento qué hizo la IA, qué decidí yo y qué errores suyos
se detectaron. Todo lo que sigue sale del registro que se llevó durante el trabajo.

## Herramientas

- **Claude Code** (extensión de VS Code) con el modelo **Claude Opus 5.5**, en modo plan primero y después
  construyendo, con subagentes para investigar, revisar y trabajar en paralelo.
- **`CLAUDE.md`**: stack, comandos y las once reglas que no se negocian (enums como única fuente de verdad, el estado
  solo cambia con `ChangeIncidentStatus`, transacción y bloqueo, log solo de inserción, errores en su campo…).
- **Skills del proyecto**, escritas antes que el código de dominio y que cada agente tenía que leer:
  `incident-domain` (qué hay que cambiar a la vez al tocar la máquina de estados), `data-fix-protocol` (cuándo una
  corrección de datos es automática y cuándo se marca) y `review-checklist` (revisión antes de cada commit).
- **Mis herramientas de auditoría** para la revisión de seguridad: la skill `repo-first-defense` y mi agente de
  seguridad (ver [`docs/auditoria-seguridad.md`](../docs/auditoria-seguridad.md)).
- Para comprobar: PHPUnit contra MySQL real, Larastan nivel 8, Pint, TypeScript, ESLint, Prettier, Chrome automatizado
  con Puppeteer (escritorio y 375 px, con la CSP aplicada) y GitHub Actions.

## Cómo lo dirigí

1. **Plan antes de código.** Le pasé el PDF y la web de Optima Retail y pedí un plan. La IA investigó en paralelo,
   con dos agentes, la marca y el negocio de Optima Retail y las versiones actuales del stack con su instalación en
   Windows. Después, otros dos agentes revisaron el borrador del plan: uno en el papel de quien corrige la prueba y
   otro buscando riesgos técnicos. El plan que aprobé recortó lo que sobraba y fijó las puertas de control.
2. **Mis decisiones**, tomadas con opciones concretas delante:
   - Inertia + React en vez de Livewire, porque es lo que domino y lo puedo defender.
   - Enseñar el montaje de IA en el repositorio (`CLAUDE.md` y skills), no solo esta carpeta.
   - Repositorio público.
   - **No** meter ninguna función de IA dentro del panel: el enunciado no la pide. Queda en «Siguientes pasos» del
     README.
   - Publicar los encargos que la IA dio a sus agentes, y de mis propios prompts solo un resumen.
3. **Por fases con puerta de control**, y cada fase de código con sus propios commits:
   - entorno;
   - esqueleto, reglas y CI;
   - dominio con sus tests;
   - frontend, diagnóstico y README, en paralelo y con ficheros separados;
   - integración;
   - revisión adversaria y auditoría.

   Cada fase tenía su puerta de tests y análisis estático y, en el frontend, revisión en un navegador real. Una vez la
   IA se saltó un paso de su propia puerta (`composer validate`) y lo pilló la CI (ver la tabla).
4. **Revisión independiente**: agentes que no habían escrito el código lo revisaron intentando romperlo. Primero
   revisaron el dominio y, al final, todo el repositorio desde cinco ángulos: enunciado, seguridad, integridad de datos,
   frontend y documentación. Después vino la auditoría de seguridad con mis herramientas. Sus hallazgos se arreglaron
   con tests de regresión.

## Prompts

### Lo que pedí yo (resumen)

1. Leer el enunciado, sacar de su web la estética y el logo, planificarlo con calma y desarrollarlo de forma
   profesional, lo más segura y estable posible, y subirlo a GitHub para que lo puedan probar.
2. Las decisiones de arriba, eligiendo entre opciones que me planteó la IA con su recomendación.
3. Ver el proceso en vivo: una copia local de la aplicación, aparte de la que usaban los agentes.
4. Una auditoría de seguridad hecha con mis herramientas de auditoría y bien documentada, y dejar a la vista los
   encargos que la IA hizo a sus agentes.

### Encargos de la IA a sus agentes (literales)

En [`prompts-agentes/`](prompts-agentes/) están, sin resumir, los encargos que la sesión principal dio a sus agentes:
- investigación de la marca y del stack;
- las dos revisiones del plan;
- el flujo de construcción en paralelo;
- las cinco revisiones adversarias finales;
- los arreglos de integridad y de frontend;
- las dos pasadas de auditoría de seguridad.

Muestran qué podía tocar cada agente, qué tenía prohibido y qué comprobaciones debía pasar.

## Qué generó la IA y qué hice yo

La IA escribió prácticamente todo el código, los tests, la CI y la documentación, siguiendo el plan y las reglas de
`CLAUDE.md`, que también redactó ella a partir del plan. Lo mío fue:
- tomar las decisiones de arriba entre las opciones que me planteó;
- aprobar el plan y sus reglas;
- pedir las revisiones y la auditoría con mis herramientas;
- pedir una copia local de la aplicación para ver la construcción en vivo.
<!-- AL CERRAR: revisión de Daniel (qué miró, qué cambió o rechazó). Solo hechos reales. -->

## Errores de la IA que se detectaron, y cómo

| Error | Cómo se vio | Arreglo |
|---|---|---|
| Supuso que Optima Retail hacía software para retail (precios, stock) | La investigación de su web: es facility management para cadenas de tiendas | Datos de ejemplo de mantenimiento de tiendas y prioridades con su vocabulario (código verde, amarillo y rojo) |
| El borrador del plan ponía el cambio de estado solo en la ficha, y el enunciado lo pide en la pantalla principal | Revisión del plan | Acciones de estado en cada fila de la lista |
| El borrador devolvía 409 en un conflicto de estado, pero Inertia reserva el 409 para sus redirecciones | Revisión del plan (leyendo el código de Inertia) | El conflicto es un error de validación en su campo; el 409 solo llega a clientes JSON |
| El borrador exigía PHP 8.4 sin necesidad; después, `composer create-project` instaló Symfony 8.1, que exige 8.4 | Revisión del plan; listando versiones tras instalar | `platform.php = 8.3.0` y Symfony 7.4: la CI prueba PHP 8.3 y 8.4 |
| Escribió `package.json` sin leerlo antes: la escritura se rechazó y npm instaló el del esqueleto (Vite 7, axios) | `npm ls` | Reescrito y reinstalado desde cero |
| `createInertiaApp` escrito como en Inertia v2 | `tsc` | Resolver con el componente, según los tipos de v3 |
| Fechas comparadas con microsegundos cuando MySQL guarda segundos | Tests | `freezeSecond()` |
| Un string suelto `'flagged_for_review'` en un controlador, contra la regla 1 de su propio `CLAUDE.md` | Autorrevisión | Método del modelo que usa el enum |
| Un array en lugar de texto en el cambio de estado provocaba un error 500 | Revisión independiente, con una petición manipulada | Lectura defensiva y test de regresión |
| Una fila insertada por SQL sin fechas tumbaba el listado | Revisión independiente | `created_at`/`updated_at` con valor por defecto en la base y test |
| Faltaba un CHECK en `incident_logs.action`: un valor desconocido rompía el historial | Revisión independiente | CHECK y test |
| Un doble clic decía «otra persona ha cambiado esta incidencia» | Revisión independiente | Mensaje «ya está …» y test |
| Al arreglar el doble clic, dos tests de «estado desactualizado» dejaron de probar lo que decían (usaban la misma transición) | Los propios tests, al fallar tras el arreglo | Escenario reescrito con transiciones distintas |
| Texto con UTF-8 inválido provocaba un error de base de datos | Revisión independiente | Regla de validación con mensaje en español |
| Se perdía el foco del teclado tras cambiar el estado desde el diálogo | Prueba automática de foco en Chrome | El foco pasa al primer botón nuevo |
| «En revisión» salía cortado a 375 px y los botones de la tabla se apilaban | Capturas de la revisión visual | Ajuste de tamaños |
| El campo «Solicitante» tenía autocompletado con el nombre propio | Autorrevisión del agente | Quitado: el solicitante es otra persona |
| Textos del diagnóstico mal puntuados y tablas de anchos distintos | Leyendo la salida real del comando | Corregido |
| El `composer.lock` no estaba al día: la IA editó `composer.json` después de generarlo y no lanzó `composer validate`, que estaba en la puerta de la fase 1 | La primera CI falló en `composer validate --strict` | `composer update --lock`, que solo cambia el hash (commit `929e434`) |
| El README explicaba cómo cerrar un caso marcado anulando su marca, pero así el siguiente diagnóstico lo vuelve a abrir, y no había forma de cerrarlo | Revisión de la documentación ejecutando el procedimiento; revisión de integridad con el ciclo completo | Comando `incidents:review`: una persona registra el paso que falta con su nombre y su motivo, y la marca queda como revisada |
| Las CHECK se podían saltar con mayúsculas, acentos o espacios finales (`'OPEN'`, `'open '`) por la intercalación de la columna, y después el panel y el diagnóstico fallaban | Revisión de integridad, con escrituras por SQL directo | Intercalación exacta en esas columnas y el diagnóstico tolera valores desconocidos; tests |
| La conexión a MySQL no fijaba zona horaria: con un servidor fuera de UTC, `--fix` dejaba de ser idempotente | Revisión de integridad, con una sesión en +02:00 | Conexión en UTC y, con evidencia de fecha futura, se marca en vez de conciliar; test |
| La CSP fallaba en abierto con un valor mal escrito, y `.env.example` la dejaba en modo informe también en producción | Revisión de seguridad | Falla en cerrado y `.env.example` sin valor fijo; test |
| Se aceptaban caracteres de control: un título escrito por SQL podía inyectar secuencias de escape en la terminal de quien ejecuta el diagnóstico | Revisión de seguridad | Regla `CleanText` y limpieza de todo lo que imprime el comando; tests |
| Los valores por defecto de sesión, caché y cola apuntaban a tablas que el proyecto no tiene | Revisión de seguridad | Valores por defecto coherentes con `.env.example` |
| La tabla cortaba la columna de acciones entre 1024 y 1195 px de ancho | Revisión del frontend, midiendo anchos reales | Tabla desde 1280 px y desplazamiento horizontal como red de seguridad |
| Abrir un diálogo y cruzar ese ancho (girar una tablet) dejaba la página bloqueada, porque el diálogo vivía dentro de la copia oculta de la lista | Revisión del frontend | El diálogo se pinta fuera de la lista (portal) |
| Un 419 o un 429 vaciaba los formularios: se trataba como un éxito | Revisión del frontend | El servidor lo devuelve también como error y el formulario conserva lo escrito; test |
| Al escribir un test, la herramienta de edición convirtió en un «<» literal la secuencia de escape Unicode que había que buscar, y la aserción dejó de probar lo que decía | El propio test, al fallar | El valor esperado se genera con `json_encode` |

Además, el agente del diagnóstico rompió a propósito su propio código de tres formas (sin exigir motivo, sin volver
a comprobar dentro del bloqueo y con una ventana de duplicados de 0 s) para confirmar que los tests lo detectaban. Lo
detectaron las tres veces.

## Qué se verificó antes de entregar

- 77 tests contra MySQL, Larastan nivel 8, Pint, TypeScript, ESLint y Prettier en verde.
- La aplicación recorrida en Chrome en escritorio y a 375 px, con la CSP aplicada: crear con errores y sin ellos,
  filtrar sin recargar, cada transición con su confirmación, conflicto entre dos pestañas, reapertura con motivo,
  comentarios y cambio de operador. Sin errores de consola ni violaciones de CSP.
- Un clon limpio siguiendo solo el README, con una base nueva: instala, compila, siembra, pasan los tests y Chrome no da
  errores.
- La salida del diagnóstico copiada en el README es la real, y la CI comprueba sus códigos de salida.
- La CI de GitHub en verde sobre el último commit: PHP 8.3 y 8.4 con MySQL 8.4, frontend en Linux, Windows y macOS, y
  auditoría de dependencias.
- Cinco revisiones adversarias finales y dos pasadas de auditoría de seguridad, con cada hallazgo corregido o
  documentado en [`docs/auditoria-seguridad.md`](../docs/auditoria-seguridad.md).

## Tiempo

Todo el 22 de octubre de 2026:
- **Planificación**, de 11:27 a 13:36: investigación, las dos revisiones del plan y mis decisiones.
- **Construcción, revisiones y auditoría**, de 13:36 a 16:10.

Es bastante más que los 90 minutos que estima el enunciado. La diferencia está en la instalación del entorno (este
equipo no tenía PHP ni MySQL), en las revisiones adversarias y en sus arreglos.
