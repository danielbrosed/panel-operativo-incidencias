# Uso de IA en esta prueba

La prueba pedía documentar el uso de IA y en la entrevista se me dijo que interesaba precisamente cómo trabajo con
ella. Por eso el montaje está a la vista en el repositorio: [`CLAUDE.md`](../CLAUDE.md) con las reglas del proyecto y
tres skills en [`.claude/skills/`](../.claude/skills/). Aquí cuento qué hizo la IA, qué decidí yo y qué errores suyos
se detectaron. Todo lo que sigue sale del registro que llevé durante el trabajo.

## Herramientas

- **Claude Code** (extensión de VS Code) con el modelo **Claude Opus 5.5**, en modo plan primero y después
  construyendo, con subagentes para investigar, revisar y trabajar en paralelo.
- **`CLAUDE.md`**: stack, comandos y las once reglas que no se negocian (enums como única fuente de verdad, el estado
  solo cambia con `ChangeIncidentStatus`, transacción y bloqueo, log solo de inserción, errores en su campo…).
- **Skills del proyecto**, escritas antes que el código de dominio y que cada agente tenía que leer:
  `incident-domain` (qué hay que cambiar a la vez al tocar la máquina de estados), `data-fix-protocol` (cuándo una
  corrección de datos es automática y cuándo se marca) y `review-checklist` (revisión antes de cada commit).
- Para comprobar: PHPUnit contra MySQL real, Larastan nivel 8, Pint, TypeScript, ESLint, Prettier, Chrome automatizado
  con Puppeteer (escritorio y 375 px, con la CSP aplicada) y GitHub Actions.

## Cómo lo dirigí

1. **Plan antes de código.** Le pasé el PDF y la web de Optima Retail y pedí un plan. La IA investigó la marca y el
   negocio (dos agentes en paralelo) y las versiones actuales de Laravel e Inertia. Después, dos agentes revisaron el
   borrador del plan: uno en el papel de quien corrige la prueba y otro buscando riesgos técnicos. El plan aprobado
   recortó lo que sobraba y fijó las puertas de control.
2. **Mis decisiones**, tomadas con opciones concretas delante:
   - Inertia + React en vez de Livewire, porque es lo que domino y lo puedo defender.
   - Enseñar el montaje de IA en el repositorio (`CLAUDE.md` y skills), no solo esta carpeta.
   - Repositorio público.
   - **No** meter ninguna función de IA dentro del panel: el enunciado no la pide. Queda en «Siguientes pasos» del
     README.
3. **Por fases con puerta de control**, cada una con commits propios:
   - entorno;
   - esqueleto, reglas y CI;
   - dominio con sus tests;
   - frontend, diagnóstico y README, en paralelo y con ficheros separados;
   - integración;
   - revisión adversaria.

   Ninguna fase pasaba sin tests, análisis estático y, en el frontend, revisión en un navegador real.
4. **Revisión independiente**: un agente que no había escrito el código revisó el dominio, con pruebas que lo
   intentaban romper. Sus hallazgos se arreglaron con tests de regresión (commit `b078556`).

## Prompts principales (resumen)

- «Lee la prueba, mira su web para sacar su estética y su logo, planifícalo con calma y desarróllalo de forma
  profesional, segura y estable; súbelo a GitHub para que lo puedan probar.»
- Investigación: «Extrae la marca de optimaretail.eu (paleta exacta, tipografía, logo, tono) y qué hace la empresa».
  «Versiones actuales de Laravel, Inertia y React, y cómo instalar PHP, Composer y MySQL en Windows sin administrador».
- Revisión del plan: «Eres el responsable técnico que corrige esta prueba: qué falta del enunciado, qué sobra, qué te
  haría rechazarla». «Prepara el plano de implementación y los riesgos».
- Construcción en paralelo, cada agente con sus ficheros, su base de datos y una lista de comprobaciones obligatorias:
  - el frontend con la marca y sus pruebas de interacción en Chrome;
  - el comando de diagnóstico con su regla de corrección y sus tests;
  - el README;
  - una revisión de solo lectura del contrato entre backend y frontend.

## Qué generó la IA

Prácticamente todo el código, los tests, la CI y la documentación, siguiendo el plan y las reglas de `CLAUDE.md`. Lo
mío fue decidir qué se construía y con qué criterio, fijar las reglas, aprobar el plan y cada fase, y revisar el
resultado: la aplicación funcionando en local y este repositorio.

## Errores de la IA que se detectaron, y cómo

| Error | Cómo se vio | Arreglo |
|---|---|---|
| Supuso que Optima Retail hacía software para retail (precios, stock) | La investigación de su web: es facility management para cadenas de tiendas | Datos de ejemplo de mantenimiento de tiendas y prioridades con su vocabulario (código verde, amarillo y rojo) |
| El borrador del plan ponía el cambio de estado solo en la ficha, y el enunciado lo pide en la pantalla principal | Revisión del plan | Acciones de estado en cada fila de la lista |
| El borrador devolvía 409 en un conflicto de estado, pero Inertia reserva el 409 para sus redirecciones | Revisión del plan (leyendo el código de Inertia) | El conflicto es un error de validación en su campo; el 409 solo llega a clientes JSON |
| Exigía PHP 8.4 sin necesidad; `composer create-project` instaló además Symfony 8.1 | Listando versiones tras instalar | `platform.php = 8.3.0`: el proyecto instala con PHP 8.3, 8.4 y 8.5 (la CI prueba 8.3 y 8.4) |
| `createInertiaApp` escrito como en Inertia v2 | `tsc` | Resolver con el componente, según los tipos de v3 |
| Fechas comparadas con microsegundos cuando MySQL guarda segundos | Tests | `freezeSecond()` |
| Un array en lugar de texto en el cambio de estado provocaba un error 500 | Revisión independiente, con una petición manipulada | Lectura defensiva y test de regresión |
| Una fila insertada por SQL sin fechas tumbaba el listado | Revisión independiente | `created_at`/`updated_at` con valor por defecto en la base y test |
| Faltaba un CHECK en `incident_logs.action`: un valor desconocido rompía el historial | Revisión independiente | CHECK y test |
| Un doble clic decía «otra persona ha cambiado esta incidencia» | Revisión independiente | Mensaje «ya está …» y test |
| Texto con UTF-8 inválido provocaba un error de base de datos | Revisión independiente | Regla de validación con mensaje en español |
| Se perdía el foco del teclado tras cambiar el estado desde el diálogo | Prueba automática de foco en Chrome | El foco pasa al primer botón nuevo |
| «En revisión» salía cortado a 375 px y los botones de la tabla se apilaban | Capturas de la revisión visual | Ajuste de tamaños |
| El campo «Solicitante» tenía autocompletado con el nombre propio | Autorrevisión del agente | Quitado: el solicitante es otra persona |
| Textos del diagnóstico mal puntuados y tablas de anchos distintos | Leyendo la salida real del comando | Corregido |

Además, el agente del diagnóstico rompió a propósito su propio código de tres formas (sin exigir motivo, sin volver
a comprobar dentro del bloqueo y con una ventana de duplicados de 0 s) para confirmar que los tests lo detectaban. Lo
detectaron las tres veces.

## Qué se verificó antes de entregar

- 77 tests contra MySQL, Larastan nivel 8, Pint, TypeScript, ESLint y Prettier en verde.
- La aplicación recorrida en Chrome en escritorio y a 375 px, con la CSP aplicada: crear con errores y sin ellos,
  filtrar sin recargar, cada transición con su confirmación, conflicto entre dos pestañas, reapertura con motivo,
  comentarios y cambio de operador. Sin errores de consola ni violaciones de CSP.
- La salida del diagnóstico copiada en el README es la real, y la CI comprueba sus códigos de salida.
<!-- AL CERRAR: clon limpio siguiendo el README, CI en verde, revisión adversaria final y revisión de Daniel. -->

## Tiempo

<!-- AL CERRAR: tiempo real. Construcción desde las 13:36 del 22-oct-2026; planificación antes. -->
