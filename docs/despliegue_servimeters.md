# Despliegue aislado en Servimeters

## Arquitectura elegida

Se utiliza la opción A solicitada por TIC: contenedor de aplicación sin base de datos embebida. El contenedor incluye Apache/PHP 8.4 y un entorno virtual Python 3.12. La conexión se realiza contra la instancia PostgreSQL institucional con una base, esquema y rol exclusivos.

```text
Internet / red interna
        |
        v
Apache institucional + TLS
        |
        | proxy HTTP a 127.0.0.1:8080
        v
Contenedor servimeters-producto:1.0.0
  - Apache 2.4 en puerto 8080
  - PHP 8.4.22
  - Python 3.12.14 / venv
        |
        v
PostgreSQL institucional
  - base: servimeters_producto
  - esquema: servimeters_producto
  - rol runtime sin DDL
```

El contenedor se ejecuta como `www-data`, sin capacidades Linux, con sistema de archivos de solo lectura y tres volúmenes escribibles: cargas, sesiones y temporales.

## Requisitos

- Docker Engine y Docker Compose Plugin autorizados por TIC.
- Acceso interno desde el contenedor a PostgreSQL por TCP 5432.
- Acceso saliente opcional a `smtp.office365.com:587`.
- Acceso saliente opcional a `generativelanguage.googleapis.com:443` cuando Gemini esté habilitado.
- Los navegadores de los usuarios pueden abrir `www.servimeters.com:443` al consultar la política de tratamiento de información; el servidor no consume ese sitio.
- No se requiere ningún puerto entrante nuevo: el servicio se publica únicamente en `127.0.0.1:8080` y Apache institucional actúa como proxy.
- No se ejecutan `pip`, Composer ni npm durante el funcionamiento de la aplicación.

## Variables de configuración

| Variable | Obligatoria | Uso |
|---|---:|---|
| `SERVIMETERS_APP_ENV` | Sí | Entorno lógico; usar `production` en sandbox y producción. |
| `SERVIMETERS_TIMEZONE` | Sí | Zona de PHP y de la sesión PostgreSQL; valor acordado: `America/Bogota`. |
| `SERVIMETERS_TRUST_PROXY` | Sí | Habilita la lectura controlada de `X-Forwarded-Proto`; debe ser `true` detrás del proxy institucional. |
| `SERVIMETERS_HTTP_PORT` | No | Puerto loopback publicado por Compose; predeterminado `8080`. |
| `SERVIMETERS_DB_DRIVER` | Sí | Motor de datos; para TIC debe ser `pgsql`. |
| `SERVIMETERS_DB_HOST` | Sí | Host o IP interna de PostgreSQL. |
| `SERVIMETERS_DB_PORT` | No | Puerto de PostgreSQL; predeterminado `5432`. |
| `SERVIMETERS_DB_NAME` | Sí | Base exclusiva asignada a la aplicación. |
| `SERVIMETERS_DB_SCHEMA` | Sí | Esquema exclusivo; `servimeters_producto`. |
| `SERVIMETERS_DB_USERNAME` | Sí | Rol de ejecución con privilegios mínimos. |
| `SERVIMETERS_DB_PASSWORD` | Según TIC | Secreto del rol; puede omitirse únicamente si TIC configura otro método de autenticación. |
| `SERVIMETERS_DB_SSLMODE` | Sí | Validación TLS de PostgreSQL; ejemplo `require`, ajustable por TIC. |
| `SERVIMETERS_SESSION_PATH` | Sí | Directorio escribible de sesiones PHP. |
| `SERVIMETERS_TEMP_PATH` | Sí | Directorio escribible para archivos XLSX temporales. |
| `SERVIMETERS_PYTHON_PATH` | Sí | Ruta absoluta al intérprete del venv. |
| `SERVIMETERS_ANALYSIS_TIMEOUT` | No | Límite del analizador en segundos; predeterminado `90`. |
| `SERVIMETERS_ANALYSIS_RETENTION_HOURS` | No | Retención de temporales; predeterminado `24`. |
| `SERVIMETERS_GEMINI_API_KEY` | No | Credencial de análisis visual; dejar vacía para procesamiento solo local. |
| `SERVIMETERS_GEMINI_MODEL` | No | Modelo opcional; predeterminado `gemini-2.5-flash`. |
| `SERVIMETERS_SMTP_ENABLED` | No | Activa notificaciones; predeterminado `false`. |
| `SERVIMETERS_SMTP_HOST`, `SERVIMETERS_SMTP_PORT`, `SERVIMETERS_SMTP_ENCRYPTION` | Si SMTP está activo | Servidor, puerto y cifrado de salida. |
| `SERVIMETERS_SMTP_USERNAME`, `SERVIMETERS_SMTP_PASSWORD` | Si SMTP está activo | Credenciales SMTP entregadas por TIC. |
| `SERVIMETERS_SMTP_FROM_EMAIL`, `SERVIMETERS_SMTP_FROM_NAME` | Si SMTP está activo | Remitente de las notificaciones. |
| `SERVIMETERS_SMTP_TIMEOUT` | No | Timeout SMTP en segundos; predeterminado `15`. |

Los secretos se entregan por un canal seguro y viven únicamente en `.env` con permisos `0600`; nunca se incorporan a la imagen ni al repositorio.

## Base de datos

Compatibilidad objetivo: PostgreSQL 13 a 18, codificación `UTF8`, zona horaria de sesión `America/Bogota`. La aplicación no exige extensiones de PostgreSQL. Para conservar búsquedas sin distinción de mayúsculas se utiliza `ILIKE` desde la aplicación.

La collation recomendada para crear la base es `es_CO.UTF-8`. Si esa locale no está instalada, TIC puede utilizar una collation ICU equivalente disponible en su versión de PostgreSQL; las reglas funcionales no dependen de ordenamiento específico de la base.

Un rol de migración ejecuta:

```bash
docker compose run --rm app php tools/apply_schema.php
psql -v app_role=servimeters_producto_app -f database/postgresql_grants.sql
```

El rol de runtime recibe solamente `USAGE` del esquema, CRUD sobre sus tablas y uso/lectura de sus secuencias. `CREATE`, `ALTER`, `CREATEDB`, `CREATEROLE` y `SUPERUSER` no son necesarios durante la operación.

El esquema usa una función `set_actualizado_en()` y triggers `BEFORE UPDATE` para mantener las columnas de auditoría. No usa vistas materializadas, procedimientos almacenados, `tablespaces`, `search_path` adicionales ni extensiones.

## Instalación en sandbox

1. Copiar el código correspondiente al tag o commit aprobado en un directorio exclusivo.
2. Copiar `.env.example` a `.env`, asignar permisos `0600` y diligenciar las variables entregadas por TIC.
3. Construir la imagen durante la ventana autorizada o cargar una imagen previamente construida y escaneada:

   ```bash
   docker compose build --pull
   ```

4. Crear el esquema con el rol temporal de migración.
5. Aplicar `database/postgresql_grants.sql` y cambiar `.env` al rol de runtime.
6. Crear el primer administrador con el rol de runtime y custodiar la contraseña temporal mostrada una sola vez:

   ```bash
   docker compose run --rm app php tools/apply_auth_migration.php
   ```

7. Iniciar el servicio:

   ```bash
   docker compose up -d
   ```

8. Confirmar `docker compose ps` y `curl http://127.0.0.1:8080/api/health.php`.
9. Capturar la evidencia técnica solicitada por TIC con `docker compose exec -T app sh tools/collect_environment_evidence.sh`.
10. Configurar el VirtualHost institucional y verificar la URL HTTPS definitiva.
11. Ejecutar las pruebas de humo descritas al final de este documento.

## VirtualHost de referencia

El certificado y las directivas TLS siguen siendo propiedad del Apache institucional.

```apache
<VirtualHost *:443>
    ServerName producto-sandbox.servimeters.com

    ProxyPreserveHost On
    ProxyPass        / http://127.0.0.1:8080/
    ProxyPassReverse / http://127.0.0.1:8080/
    RequestHeader set X-Forwarded-Proto "https"

    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"

    # Mantener aquí las directivas SSL institucionales existentes.
</VirtualHost>
```

La aplicación debe tener `SERVIMETERS_TRUST_PROXY=true`. No requiere `AllowOverride` porque el contenedor deniega explícitamente las carpetas internas desde su VirtualHost.

## Directorios y persistencia

| Ruta del contenedor | Uso | Propietario | Persistencia |
|---|---|---|---|
| `/var/www/html/storage/uploads` | Adjuntos, matrices y análisis | `www-data:www-data` | Volumen y backup |
| `/var/www/html/storage/sessions` | Sesiones PHP | `www-data:www-data` | Volumen; puede purgarse al expirar |
| `/var/www/html/tmp` | Generación temporal de XLSX | `www-data:www-data` | Volumen temporal |
| `/tmp` | PID/locks internos de Apache | `www-data:www-data` | `tmpfs`, no persistente |

El resto de la imagen se monta en modo de solo lectura. Los archivos actuales de desarrollo bajo `storage/uploads` están excluidos del contexto Docker.

## Recursos iniciales

- CPU: 1.5 vCPU.
- Memoria del contenedor: 768 MB.
- Memoria máxima por petición PHP: 512 MB.
- Procesos: 128.
- Timeout PHP: 120 segundos.
- Timeout del analizador: 90 segundos.
- Tamaño máximo por imagen: 12 MB.
- Tamaño máximo por ficha técnica: 20 MB.
- `post_max_size` y `upload_max_filesize`: 40 MB.
- Disco inicial recomendado: 5 GB para volúmenes, sujeto a monitoreo durante sandbox.

La aplicación no usa workers, colas ni servicios systemd. Se recomienda ejecutar diariamente la limpieza de análisis abandonados:

```bash
docker compose exec -T app php tools/cleanup_analysis_temp.php
```

Retención predeterminada: 24 horas, configurable con `SERVIMETERS_ANALYSIS_RETENTION_HOURS`.

## Actualización

1. Respaldar PostgreSQL y el volumen `servimeters_uploads`.
2. Construir o cargar la nueva imagen con un tag distinto.
3. Ejecutar migraciones con el rol temporal de migración.
4. Ejecutar `docker compose up -d`.
5. Verificar health check y pruebas de humo.
6. Conservar la imagen anterior hasta cerrar la ventana.

## Rollback

1. Detener nuevas escrituras mediante el VirtualHost o una ventana controlada.
2. Restaurar el backup de PostgreSQL si se aplicaron migraciones no compatibles hacia atrás.
3. Restaurar el volumen de uploads si fue modificado.
4. Cambiar el tag de imagen al anterior y ejecutar `docker compose up -d`.
5. Confirmar el health check y los servicios institucionales preexistentes.

La desinstalación se realiza con `docker compose down`. Los volúmenes no se eliminan salvo orden explícita y posterior a un respaldo validado.

## Respaldo y restauración

Respaldo lógico recomendado:

```bash
pg_dump --format=custom --no-owner --schema=servimeters_producto servimeters_producto > servimeters_producto.dump
```

TIC debe incorporar el volumen de uploads a su solución institucional de backups. La restauración se valida primero en sandbox con `pg_restore` y una copia del volumen.

## Pruebas de humo

1. `GET /api/health.php` devuelve HTTP 200.
2. La URL HTTP institucional redirige a HTTPS.
3. Un administrador inicia sesión y ve todas las fases autorizadas.
4. El panel lateral se oculta y se muestra conservando el estado.
5. Un Comercial 1 solo consulta sus solicitudes asignadas.
6. Se registra una solicitud con ficha técnica y se descarga el adjunto autenticado.
7. Se carga y descarga una matriz XLSX.
8. Se crea una precotización y se validan sus totales.
9. El analizador Python finaliza antes de 90 segundos.
10. Se cierra sesión y la cookie queda marcada `Secure`, `HttpOnly` y `SameSite=Lax`.

## Logs y soporte

Apache envía access log a stdout y error log a stderr; el runtime de contenedores debe aplicar rotación (`max-size` y `max-file` o el estándar institucional). La aplicación no registra contraseñas, tokens ni cadenas de conexión.

Responsable funcional y canal de soporte: por definir formalmente con TIC antes de producción.
