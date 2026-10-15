# Importacion en MySQL para previsualizar la base de datos

Este directorio contiene un script compatible con MySQL para previsualizar la estructura propuesta de la base de datos.

Archivo principal:

```text
database/mysql_preview_schema.sql
```

## Importar con phpMyAdmin

1. Abrir phpMyAdmin.
2. Entrar al servidor MySQL.
3. Ir a la pestana **Importar**.
4. Seleccionar el archivo `database/mysql_preview_schema.sql`.
5. Ejecutar la importacion.

El script crea la base de datos:

```text
servimeters_constructor_normativo
```

Si el hosting o servidor no permite crear bases de datos desde el script, crea primero la base de datos manualmente y elimina o comenta estas lineas del archivo SQL:

```sql
CREATE DATABASE IF NOT EXISTS servimeters_constructor_normativo
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE servimeters_constructor_normativo;
```

## Importar con MySQL Workbench

1. Abrir MySQL Workbench.
2. Conectarse al servidor.
3. Ir a **File > Open SQL Script**.
4. Seleccionar `database/mysql_preview_schema.sql`.
5. Ejecutar el script completo.
6. Refrescar el panel de schemas.

## Importar por consola

Desde la carpeta raiz del proyecto:

```bash
mysql -u usuario -p < database/mysql_preview_schema.sql
```

## Que contiene el script

El script crea las tablas principales:

- `product_line`
- `family`
- `subfamily`
- `normative_test`
- `subfamily_test_requirement`
- `classification_combination`
- `classification_combination_family`
- `subfamily_combination`
- `subfamily_combination_item`
- `project`
- `project_toy`
- `project_toy_subfamily`
- `audit_log`

Tambien inserta tres lineas iniciales de producto:

- Juguetes Colombia Resolucion 0686 de 2018.
- RETIE Colombia Resolucion 40117 de 2024.
- RETILAP Colombia Resolucion 40150 de 2024.

## Nota importante

Este archivo es solo para previsualizar la estructura en MySQL.

La base de datos oficial solicitada para el proyecto es SQL Server 2019, por lo que el script final para produccion debe generarse en sintaxis T-SQL y ajustarse a las politicas del area de TI.

