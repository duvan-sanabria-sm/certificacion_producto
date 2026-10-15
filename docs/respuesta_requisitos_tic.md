# Respuesta técnica a requisitos de despliegue

| Sección | Requisito | Estado propuesto | Ajuste implementado | Evidencia |
|---|---|---|---|---|
| 2.2 | B-01 Aislamiento | Cumple con ajuste | Contenedor propio, filesystem de solo lectura, límites y sin capacidades | `Dockerfile`, `docker-compose.yml` |
| 2.2 | B-02 Python en venv | Cumple | Python 3.12.14 en `/opt/servimeters/venv`; invocación absoluta configurable | `requirements.txt`, `config/analysis.php` |
| 2.2 | B-03 PostgreSQL | Cumple con ajuste | Driver `pdo_pgsql`, esquema y consultas portables, rol CRUD | `database/postgresql_schema.sql`, `database/postgresql_grants.sql` |
| 2.2 | B-04 HTTPS / proxy | Cumple | Reconocimiento controlado de `X-Forwarded-Proto`; proxy solo por loopback | `config/runtime.php`, `docs/despliegue_servimeters.md` |
| 2.2 | B-05 Sin secretos | Cumple | Credenciales por entorno; `.env` excluido de Git y Docker | `.env.example`, `config/database.php` |
| 2.2 | B-06 Usuario no privilegiado | Cumple | Contenedor ejecutado como `www-data`, sin capabilities | `Dockerfile`, `docker-compose.yml` |
| 2.2 | B-07 Escritura acotada | Cumple | Solo uploads, sesiones, temporales y `/tmp` aislado | `docker-compose.yml` |
| 2.2 | B-08 Sin datos ajenos | Cumple | Base, esquema, rol y volúmenes propios | Scripts de grants y compose |
| 2.2 | B-09 Reversibilidad | Cumple | Procedimientos de backup, rollback y desinstalación | `docs/despliegue_servimeters.md` |
| 2.2 | B-10 Recursos acotados | Cumple | 1.5 CPU, 768 MB, 128 procesos, timeouts y healthcheck | `docker-compose.yml`, configuración PHP |
| 2.3 | Rango PHP | Cumple | PHP mínimo 8.0; imagen validable fijada en PHP 8.4.22 | `Dockerfile` |
| 2.3 | PostgreSQL | Cumple con ajuste | Compatibilidad objetivo PostgreSQL 13–18, sin extensiones | Esquema PostgreSQL |
| 3 | Modalidad | Opción A | Contenedor de aplicación conectado a PostgreSQL institucional | Paquete Docker |

La evidencia literal de versiones y módulos se obtiene dentro de la imagen aprobada con `docker compose exec -T app sh tools/collect_environment_evidence.sh`. Debe adjuntarse su salida a la entrega de sandbox.

## Componentes sin gestor de dependencias

- Framework PHP: ninguno; PHP nativo.
- Composer: no aplica, no hay paquetes PHP externos.
- Build frontend: no aplica; CSS y JavaScript nativos se entregan listos.
- npm en runtime: no aplica.

La aprobación definitiva queda sujeta a ejecutar el esquema y las pruebas funcionales contra la versión exacta de PostgreSQL que TIC confirme para sandbox.
