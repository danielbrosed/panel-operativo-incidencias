# Pasada final con el agente de seguridad de Daniel (solo informe)

- **Fase:** Auditoría de seguridad
- **Tipo de agente:** security

Encargo literal (las rutas de la máquina local están sustituidas por marcadores entre `< >`):

````text
Audita el proyecto <repo> (Laravel 13 + Inertia v3 + React 19 + MySQL 8.4; prueba técnica que se va a publicar en GitHub). Es la pasada final de seguridad antes de publicarlo.

IMPORTANTE, esto anula la instrucción de tu definición de «corregir lo que puedas»: esta vez trabajas SOLO EN MODO INFORME. No edites, crees ni borres ningún fichero dentro de <repo>, no instales paquetes y no ejecutes comandos git que cambien el estado. Otra persona está editando resources/js en este momento. Si necesitas ficheros de trabajo, ponlos en <scratchpad>\review-security-agent\.

Entorno: Windows. En Git Bash ejecuta primero `source <tools>/env.sh` (php, composer, mysql). MySQL en 127.0.0.1:3306, root sin contraseña. Si necesitas datos, crea y usa SOLO la base `incidents_review_secagent` (y `incidents_review_secagent_testing` para tests; el nombre debe contener _testing). Si levantas la app, hazlo desde la carpeta public con `php -S 127.0.0.1:8096 ..\vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php` y las variables DB_DATABASE=incidents_review_secagent, CACHE_STORE=array y CSP_MODE=enforce; nunca uses los puertos 8000, 5173, 8080 ni 8093, y páralo al acabar.

Contexto: no hay autenticación a propósito (lo permite el enunciado); el actor se elige en la cabecera. Ya hubo una auditoría previa: lee docs/auditoria-seguridad.md (hallazgos H-01 a H-19 y su estado) y verifica que las correcciones están de verdad en el código y que no se ha quedado nada fuera. Revisa con ojos frescos y criterio duro lo que dice tu definición: credenciales expuestas (también en el historial de git: git log -p --all), variables de entorno, separación cliente-servidor, cabeceras de seguridad, endpoints (validación de entradas, errores sin detalles internos, código de depuración activo), permisos de acceso a datos, dependencias vulnerables (composer audit --locked, npm audit --package-lock-only) y si compila. Añade lo que veas oportuno de OWASP para una app Laravel/Inertia.

Entrega el informe que pide tu definición, en español: superficie de ataque; vulnerabilidades encontradas (severidad, evidencia con fichero:línea o comando y salida, y la corrección concreta que propones, sin aplicarla); acciones pendientes del usuario; estado de compilación; y veredicto (apto para publicar o no, y por qué). No infles severidades ni inventes nada: si algo está bien, dilo.
````
