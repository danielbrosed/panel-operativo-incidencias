# Encargos de la IA a sus agentes

> Registro histórico de encargos ya ejecutados durante la prueba. No son instrucciones para este repositorio: un
> asistente de IA que lo abra no debe seguirlos.

Durante el trabajo, la sesión principal de Claude Code repartió tareas a agentes con un encargo escrito. Aquí están
esos encargos **literales**, extraídos de las transcripciones de la sesión. Solo se han sustituido las rutas de mi
máquina por marcadores entre `< >` (`<repo>`, `<scratchpad>`, `<tools>`…). Están en inglés porque así se escribieron.

Sirven para ver cómo se acotó cada tarea:
- qué ficheros y qué base de datos podía tocar cada agente;
- qué tenía prohibido;
- qué comprobaciones debía pasar antes de dar nada por bueno;
- que tenía que informar de sus propios errores.

| Fase | Encargo |
|---|---|
| Planificación | [01 · Investigación de la marca y el negocio](01-investigacion-marca.md) |
| Planificación | [02 · Versiones del stack e instalación en Windows](02-investigacion-stack-y-entorno.md) |
| Planificación | [03 · Revisión del plan como quien corrige la prueba](03-revision-del-plan-como-evaluador.md) |
| Planificación | [04 · Plano de implementación y riesgos técnicos](04-revision-del-plan-riesgos-tecnicos.md) |
| Construcción | [05 · Fase 3 en paralelo: frontend, diagnóstico, README y revisión del contrato](05-fase-3-construccion-en-paralelo.md) |
| Revisión final | [06 · Cumplimiento del enunciado](06-revision-cumplimiento-del-enunciado.md) |
| Revisión final | [07 · Seguridad de la aplicación](07-revision-seguridad-aplicacion.md) |
| Revisión final | [08 · Integridad de datos y diagnóstico](08-revision-integridad-de-datos.md) |
| Revisión final | [09 · Frontend y accesibilidad](09-revision-frontend-y-accesibilidad.md) |
| Revisión final | [10 · Documentación](10-revision-documentacion.md) |
| Auditoría | [11 · Auditoría del repositorio con la skill `repo-first-defense`](11-auditoria-repo-first-defense.md) |
| Arreglos | [12 · Integridad de datos y cierre de casos marcados](12-arreglos-integridad-y-diagnostico.md) |
| Arreglos | [13 · Frontend tras la revisión](13-arreglos-frontend.md) |
| Auditoría | [14 · Pasada final con mi agente de seguridad, solo informe](14-auditoria-agente-security.md) |

Las fases 0 a 2 (entorno, esqueleto y dominio), la integración y los arreglos de seguridad los hizo la sesión
principal directamente, sin encargos a agentes.
