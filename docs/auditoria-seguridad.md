# Auditoría de seguridad · Panel operativo de incidencias

| | |
|---|---|
| **Proyecto** | `danielbrosed/panel-operativo-incidencias` (prueba técnica para Optima Retail) |
| **Auditor** | Daniel Brosed |
| **Fecha** | 22 de octubre de 2026 |
| **Versión auditada** | Rama `main` antes de hacer público el repositorio. Las correcciones están en el historial a continuación |
| **Método** | Revisión dirigida por el auditor y ejecutada con agentes de IA, con pruebas reproducibles (ver [Método](#método)) |
| **Veredicto** | **Apto para publicar.** Sin hallazgos críticos ni altos explotables; los de severidad media y baja están corregidos y cubiertos por tests |

## Resumen

La aplicación es un panel interno sin autenticación, a propósito, como permite el enunciado. Eso deja fuera del
análisis el control de acceso y lo centra en cuatro frentes:
- la integridad del historial de incidencias;
- la robustez frente a entradas manipuladas y escrituras que se saltan la aplicación;
- la protección del navegador (XSS, CSRF, CSP);
- la cadena de suministro de un repositorio que va a ser público y que incluye instrucciones para asistentes de IA.

Las garantías de fondo aguantan pruebas reales:
- **Integridad bajo concurrencia.** Estado y log se guardan juntos o no se guardan. Dos peticiones simultáneas sobre la
  misma incidencia se serializan con un bloqueo de fila; se probó con dos conexiones a MySQL a la vez.
- **Defensas del navegador.** El CSRF protege todas las escrituras, React escapa toda la salida y la CSP con nonce no
  admite `unsafe-inline` ni `unsafe-eval`.
- **Repositorio limpio.** No hay secretos ni en los ficheros ni en el historial.

Los hallazgos estaban en los bordes. Entradas raras provocaban errores 500 en vez de errores de validación. Las
restricciones CHECK de la base de datos se podían saltar por la intercalación. Y había valores por defecto que
fallaban en abierto. Todos están corregidos.

## Alcance

- **Dentro:**
  - código de la aplicación (`app/`, `bootstrap/`, `routes/`, `config/`, `resources/`);
  - esquema y migraciones;
  - el comando `incidents:diagnose` y el cierre de casos `incidents:review`;
  - dependencias (Composer y npm) y la CI de GitHub Actions;
  - el historial completo de git;
  - las instrucciones para asistentes de IA que se publican (`CLAUDE.md`, `.claude/skills/`, `ai-process/`).
- **Fuera:**
  - control de acceso (no hay autenticación por diseño);
  - despliegue e infraestructura (la prueba no se despliega);
  - pentest de red.

## Método

Fijé el alcance y los criterios. La ejecución la hicieron agentes de IA (Claude Code) con mis herramientas de
auditoría, cada uno con un encargo de solo lectura, y después revisé y validé sus conclusiones. Los encargos literales
están en [`ai-process/prompts-agentes/`](../ai-process/prompts-agentes/).

1. **Repositorio y cadena de suministro**, con mi skill `repo-first-defense`:
   - secretos en ficheros e historial (patrones de claves, entropía, datos personales y rutas locales);
   - integridad de los lockfiles, firmas de npm y paquetes sospechosos;
   - configuración de GitHub Actions;
   - instrucciones para agentes de IA tratadas como código ejecutable (inyección de prompts, Unicode oculto,
     órdenes peligrosas).
2. **Aplicación**, con pruebas en vivo:
   - entradas manipuladas en cada ruta (arrays, UTF-8 inválido, caracteres de control, cadenas enormes);
   - XSS almacenado en todos los campos visibles;
   - CSRF y suplantación de método;
   - cabeceras y CSP en modo local y producción;
   - límite de escrituras;
   - divulgación de información en errores.
3. **Integridad de datos**: carreras reales con dos conexiones a MySQL, escrituras que se saltan la aplicación, el
   comando de diagnóstico ante 21 corrupciones distintas y su idempotencia.
4. **Mi agente de seguridad** (`security`), en modo informe, como pasada final con ojos nuevos.

Las pruebas que merecían quedarse están en el repositorio: [`SecurityTest`](../tests/Feature/SecurityTest.php),
[`SecurityHeadersTest`](../tests/Feature/SecurityHeadersTest.php), [`IntegrityTest`](../tests/Feature/IntegrityTest.php)
y los tests de validación y del diagnóstico.

### Severidades

| Severidad | Criterio |
|---|---|
| Crítica / Alta | Explotable desde fuera con impacto en datos o en terceros |
| Media | Rompe la disponibilidad o la integridad con una entrada o una escritura plausible |
| Baja | Requiere condiciones poco probables, o es un valor por defecto inseguro sin explotación directa |
| Informativa | Endurecimiento recomendado o decisión de diseño que conviene dejar escrita |

## Hallazgos

| ID | Severidad | Hallazgo | Estado | Corrección |
|---|---|---|---|---|
| H-01 | Media | Un array en lugar de texto en el cambio de estado provocaba un error 500 | Corregido | Lectura defensiva antes de validar; test de regresión |
| H-02 | Media | Texto con UTF-8 inválido llegaba a MySQL y daba un error 500 | Corregido | Regla `CleanText` con mensaje en español; test |
| H-03 | Media | Una fila insertada por SQL sin fechas tumbaba el listado y la ficha | Corregido | `created_at` y `updated_at` con valor por defecto en la base; test |
| H-04 | Media | Las CHECK de `status`, `priority` y `action` se saltaban con mayúsculas, acentos o espacios finales (`'OPEN'`, `'open '`), por la intercalación de la columna; después el panel y el diagnóstico fallaban | Corregido | Intercalación exacta `utf8mb4_0900_bin` en esas columnas y en `old_value`/`new_value`; el diagnóstico trata un valor desconocido como caso para revisión manual; tests |
| H-05 | Media | Sin zona horaria en la conexión, un servidor MySQL fuera de UTC fechaba con horas de diferencia lo escrito por SQL, y la corrección automática dejaba de ser idempotente | Corregido | Conexión en `+00:00`; una conciliación cuya evidencia está en el futuro pasa a revisión manual; test |
| H-06 | Baja | Faltaba un CHECK en `incident_logs.action`: un valor desconocido rompía el historial | Corregido | CHECK y test |
| H-07 | Baja | Anular un log no era atómico: dos instancias cargadas a la vez podían anularlo dos veces y pisar el motivo | Corregido | Anulación con `UPDATE … WHERE voided_at IS NULL`, motivo obligatorio y CHECK `(voided_at IS NULL) = (voided_reason IS NULL)`; test |
| H-08 | Baja | Se aceptaban caracteres de control y de dirección bidi en los textos; un título escrito por SQL podía inyectar secuencias de escape en la terminal de quien ejecuta el diagnóstico | Corregido | `CleanText` los rechaza (saltos de línea solo en campos de varias líneas) y el comando limpia todo lo que imprime; tests |
| H-09 | Baja | `.env.example` dejaba la CSP en modo informe en cualquier entorno, y un valor mal escrito la desactivaba | Corregido | Sin valor fijo en `.env.example` (informe solo en local) y la CSP falla en cerrado: cualquier valor desconocido la aplica; test |
| H-10 | Baja | Los valores por defecto de sesión, caché y cola apuntaban a tablas que no existen: un despliegue sin esas variables daba 500 en todas las páginas | Corregido | Valores por defecto `file`, `file`, `sync` y `mysql` |
| H-11 | Baja | Las DevTools de Inertia, activas por defecto en local, sirven sin autenticación las peticiones grabadas. La primera corrección solo tocaba `.env.example`, así que sin la variable seguían encendidas (lo vio la pasada final) | Corregido | Solo se encienden con `INERTIA_DEVTOOLS_ENABLED=true` explícito (`AppServiceProvider`) |
| H-12 | Baja | Los encargos publicados en `ai-process/prompts-agentes/` están escritos en imperativo y un asistente de IA podría tomarlos como tareas | Corregido | Aviso de «registro histórico» en la carpeta y en `CLAUDE.md` |
| H-13 | Informativa | Las URLs absolutas salen de la cabecera `Host` | Corregido | `trustHosts()` fuera de local y tests |
| H-14 | Informativa | `minimum-stability: dev` heredado de la plantilla (el lock no tenía versiones inestables) | Corregido | `stable` |
| H-15 | Informativa | `CLAUDE.md` indica `migrate:fresh`, que borra la base de `.env` | Corregido | Aviso de pedir confirmación |
| H-16 | Informativa | Las páginas de error por defecto de Laravel usan estilos en línea sin nonce: con la CSP aplicada se ven sin estilo | Aceptado | Se leen bien y en español; no compensa reescribirlas para la prueba |
| H-17 | Informativa | Detrás de un proxy que termine TLS no se envía HSTS y el límite de escrituras se compartiría por IP del proxy | Documentado | Configurar `trustProxies` al desplegar (README, «Seguridad») |
| H-18 | Informativa | Sin autenticación, el autor de cada log es el operador elegido en «Actuando como» | Por diseño | No exponer el panel sin autenticación (README) |
| H-19 | Informativa | Tras publicar, faltarán alertas de dependencias y escaneo de secretos de GitHub | Al publicar | Activar Dependabot, *secret scanning* con *push protection* y aprobación de la CI para contribuidores externos |
| H-20 | Informativa | `CleanText` dejaba pasar caracteres invisibles de formato (espacio de anchura cero, marcas LRM/RLM, BOM, separadores de línea) | Corregido | Una sola lista de caracteres prohibidos para la regla y para todo lo que imprimen los comandos; test |
| H-21 | Informativa | Rutas `storage/{path}` del framework activas sin uso (protegidas por firma) | Corregido | `serve` desactivado |
| H-22 | Informativa | La página de salud `/up` del framework carga un script de un CDN | Corregido | Ruta quitada: la aplicación no la usa |
| H-23 | Informativa | `.gitignore` solo excluía tres variantes de `.env` | Corregido | `.env.*` con la excepción de `.env.example` |
| H-24 | Informativa | Las cookies no llevan `Secure` por defecto | Documentado | `SESSION_SECURE_COOKIE=true` al desplegar por HTTPS (README) |
| H-25 | Informativa | Las respuestas JSON de 404 nombran la clase del modelo | Aceptado | El código es público y la aplicación no pide JSON |
| H-26 | Informativa | El historial de una ficha no tiene límite y se puede inflar con comentarios | Aceptado | Lo cubre el límite de escrituras y la condición de no exponer el panel sin autenticación (H-18) |

## Verificado sin hallazgos

- **CSRF.** Toda escritura sin token devuelve 419, también con `_method` o `X-HTTP-Method-Override`. Las rutas GET no
  tienen efectos.
- **XSS.** Las cargas almacenadas en título, descripción, solicitante, tienda, comentario y motivo se muestran como
  texto en la lista, la ficha, el historial, los avisos y el `<title>`. Los datos iniciales de la página usan
  `JSON_HEX_TAG`. No hay `dangerouslySetInnerHTML`.
- **CSP y cabeceras.** El nonce cambia en cada petición y no hay `unsafe-inline` ni `unsafe-eval`. Además:
  `object-src 'none'`, `base-uri` y `form-action` en `'self'`, `frame-ancestors 'none'`, `nosniff`, `Referrer-Policy`,
  `Permissions-Policy` y COOP. Las páginas 404 y 429 también llevan las cabeceras, y HSTS solo sale por HTTPS.
- **Asignación masiva.** `status`, `due_at`, `id` y `created_at` en la petición se ignoran.
- **Límite de escrituras.** 60 por minuto e IP en las cuatro rutas que escriben; Inertia recibe una redirección con
  aviso.
- **Errores en producción.** Ni la página 500 ni la respuesta JSON muestran el mensaje ni la traza.
- **Diagnóstico.** Al SQL interpolado solo llegan valores de los enums y un entero de configuración, y el id de la
  incidencia va como parámetro.
- **Secretos y datos personales.** Ninguno en ficheros ni en el historial completo. `.env` no se ha versionado nunca,
  las capturas no llevan metadatos y los datos de ejemplo son ficticios.
- **Dependencias.** `composer audit` y `npm audit` salen a 0, las 198 firmas de npm están verificadas y no hay plugins
  de Composer activos.
- **CI.** Permisos `contents: read`, `persist-credentials: false` y disparo por `pull_request` (no `_target`). Las
  acciones están fijadas por SHA y comprobadas contra su etiqueta, y no hay entradas de terceros en los pasos `run`.
- **Instrucciones para agentes.** `CLAUDE.md` y las skills no contienen órdenes de red, lectura de secretos ni Unicode
  oculto. No hay `settings.json`, hooks ni servidores MCP versionados.

## Riesgos residuales

- **Escrituras que se saltan la aplicación** (SQL a mano, importaciones). La aplicación no puede impedirlas. Las
  mitigan las CHECK, los permisos por columna recomendados para producción (README) y el propio diagnóstico.
- **El modelo protege el historial, pero una escritura con el *query builder* lo esquiva.** En producción, el usuario
  de la aplicación debería tener solo `INSERT` y `UPDATE (voided_at, voided_reason)` sobre `incident_logs`.
- **Sin autenticación.** Es la condición de la prueba, no apta para producción.

## Recomendaciones para producción

1. `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` real (de él depende `trustHosts`), `SESSION_SECURE_COOKIE=true`
   y CSP aplicada (es el valor por defecto fuera de local).
2. Autenticación y que `CurrentActor` devuelva el usuario autenticado.
3. Usuario de base de datos con permisos mínimos (`GRANT` en el README) y migraciones con otro usuario.
4. `trustProxies` si hay un proxy delante, para HSTS y para que el límite de escrituras use la IP real.
5. Dependabot, *secret scanning* y aprobación de la CI para contribuidores externos en GitHub.
6. Copia de la base antes de cada `incidents:diagnose --fix`.

---

Daniel Brosed · auditor de seguridad · [danielbrosed.com](https://danielbrosed.com)
