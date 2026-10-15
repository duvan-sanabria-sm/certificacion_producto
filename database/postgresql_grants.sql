-- Ejecutar en psql después de postgresql_schema.sql.
-- Ejemplo: psql -v app_role=servimeters_producto_app -f database/postgresql_grants.sql

\if :{?app_role}
\else
\set app_role servimeters_producto_app
\endif

REVOKE CREATE ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA servimeters_producto FROM :"app_role";
REVOKE ALL PRIVILEGES ON ALL TABLES IN SCHEMA servimeters_producto FROM :"app_role";
REVOKE ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA servimeters_producto FROM :"app_role";
GRANT USAGE ON SCHEMA servimeters_producto TO :"app_role";
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA servimeters_producto TO :"app_role";
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA servimeters_producto TO :"app_role";

ALTER DEFAULT PRIVILEGES IN SCHEMA servimeters_producto
  GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO :"app_role";
ALTER DEFAULT PRIVILEGES IN SCHEMA servimeters_producto
  GRANT USAGE, SELECT ON SEQUENCES TO :"app_role";
