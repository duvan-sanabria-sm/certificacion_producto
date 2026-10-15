# Documento tecnico: migracion a PHP y SQL Server 2019

## 1. Objetivo

Este documento propone una arquitectura inicial para migrar el proyecto `Constructor ISO de juguetes y ensayos sin repetir` desde un archivo HTML autocontenido hacia una solucion basada en PHP y SQL Server 2019, alineada con un entorno corporativo y con criterios esperados por el area de TI.

El objetivo principal es separar la interfaz, la logica de negocio y la informacion normativa, para que el sistema pueda mantenerse, auditarse, escalarse e integrarse con otros procesos internos de Servimeters.

## 2. Estado actual del proyecto

El proyecto actual esta implementado en el archivo:

```text
constructor_iso_juguetes.html
```

Este archivo contiene:

- Interfaz HTML.
- Estilos CSS embebidos.
- Logica JavaScript.
- Matriz de datos en formato JSON dentro del mismo HTML.
- Persistencia local usando `localStorage`.

La aplicacion permite construir juguetes por familias/subfamilias, calcular ensayos requeridos sin repetir y exportar resultados a CSV, JSON o PDF mediante impresion del navegador.

## 3. Alcance de la migracion

La migracion propuesta cubre:

- Implementar backend en PHP.
- Usar SQL Server 2019 como base de datos principal.
- Separar catalogos normativos de datos transaccionales.
- Migrar la matriz JSON embebida a tablas relacionales.
- Mantener el flujo funcional actual del usuario.
- Permitir almacenamiento centralizado de proyectos y juguetes.
- Preparar la solucion para auditoria, control de usuarios y mantenimiento futuro.

No se recomienda cambiar toda la interfaz desde el inicio. La primera fase puede conservar gran parte del HTML/CSS/JavaScript actual y reemplazar progresivamente el origen de datos por servicios PHP.

## 4. Arquitectura propuesta

La arquitectura recomendada es una aplicacion web tradicional de tres capas:

```text
Navegador
   |
   | HTML / CSS / JavaScript
   v
Servidor Web + PHP
   |
   | PDO_SQLSRV o sqlsrv
   v
SQL Server 2019
```

### 4.1 Frontend

Responsabilidades:

- Presentar formularios y tablas.
- Consumir endpoints PHP.
- Ejecutar validaciones basicas de interfaz.
- Mostrar resultados, filtros y exportaciones.

Tecnologias sugeridas:

- HTML.
- CSS.
- JavaScript nativo.

Se puede mantener una interfaz similar a la actual para reducir riesgo de migracion.

### 4.2 Backend PHP

Responsabilidades:

- Exponer endpoints para catalogos.
- Guardar y consultar proyectos.
- Ejecutar calculos de ensayos consolidados.
- Validar permisos.
- Centralizar reglas de negocio.
- Exportar CSV/JSON desde servidor si TI lo requiere.

Tecnologias sugeridas:

- PHP 8.x.
- Extension `pdo_sqlsrv` o `sqlsrv`.
- IIS o Apache, segun estandar interno.
- Variables de entorno o archivo de configuracion fuera del repositorio para credenciales.

### 4.3 Base de datos SQL Server 2019

Responsabilidades:

- Almacenar catalogos normativos.
- Almacenar familias, subfamilias, ensayos y combinaciones.
- Almacenar proyectos de usuario.
- Mantener integridad referencial.
- Permitir auditoria y trazabilidad.

## 5. Estructura sugerida del proyecto PHP

```text
/
|-- public/
|   |-- index.php
|   |-- assets/
|   |   |-- css/
|   |   |-- js/
|   |   `-- img/
|
|-- app/
|   |-- Controllers/
|   |   |-- CatalogController.php
|   |   |-- ProjectController.php
|   |   `-- CalculationController.php
|   |
|   |-- Models/
|   |   |-- Family.php
|   |   |-- Subfamily.php
|   |   |-- Test.php
|   |   `-- Project.php
|   |
|   |-- Services/
|   |   |-- Database.php
|   |   |-- CatalogService.php
|   |   |-- CalculationService.php
|   |   `-- ExportService.php
|   |
|   `-- Support/
|       |-- Response.php
|       `-- Validator.php
|
|-- config/
|   `-- database.example.php
|
|-- database/
|   |-- schema.sql
|   |-- indexes.sql
|   `-- seed_initial_data.sql
|
|-- docs/
|   `-- arquitectura_php_sqlserver.md
|
|-- storage/
|   `-- logs/
|
`-- README.md
```

Notas:

- `public/` debe ser la unica carpeta expuesta por el servidor web.
- `config/database.example.php` no debe contener credenciales reales.
- `storage/logs/` debe estar fuera del acceso publico.
- Los scripts SQL deben mantenerse versionados.

## 6. Modelo de datos inicial

El modelo se divide en dos grupos:

- Catalogos normativos: informacion estable de lineas, familias, subfamilias, ensayos y combinaciones.
- Datos transaccionales: proyectos creados por usuarios y juguetes asociados.

### 6.1 Lineas de producto

Representan el marco normativo o grupo de productos.

Ejemplos:

- Juguetes Colombia Resolucion 0686 de 2018.
- RETIE.
- RETILAP.
- Vajillas.

```sql
CREATE TABLE dbo.ProductLine (
    ProductLineId INT IDENTITY(1,1) PRIMARY KEY,
    Code NVARCHAR(120) NOT NULL UNIQUE,
    Name NVARCHAR(250) NOT NULL,
    Country NVARCHAR(80) NULL,
    Regulation NVARCHAR(250) NULL,
    IsActive BIT NOT NULL DEFAULT 1,
    CreatedAt DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
    UpdatedAt DATETIME2 NULL
);
```

### 6.2 Familias

Agrupan subfamilias dentro de una linea de producto.

```sql
CREATE TABLE dbo.Family (
    FamilyId INT IDENTITY(1,1) PRIMARY KEY,
    ProductLineId INT NOT NULL,
    Code NVARCHAR(180) NOT NULL,
    Name NVARCHAR(250) NOT NULL,
    IsActive BIT NOT NULL DEFAULT 1,
    CreatedAt DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
    UpdatedAt DATETIME2 NULL,
    CONSTRAINT FK_Family_ProductLine
        FOREIGN KEY (ProductLineId) REFERENCES dbo.ProductLine(ProductLineId),
    CONSTRAINT UQ_Family_ProductLine_Code
        UNIQUE (ProductLineId, Code)
);
```

### 6.3 Subfamilias

Son las unidades que se seleccionan para construir un juguete o item.

```sql
CREATE TABLE dbo.Subfamily (
    SubfamilyId INT IDENTITY(1,1) PRIMARY KEY,
    FamilyId INT NOT NULL,
    Code NVARCHAR(220) NOT NULL,
    Name NVARCHAR(300) NOT NULL,
    IsActive BIT NOT NULL DEFAULT 1,
    CreatedAt DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
    UpdatedAt DATETIME2 NULL,
    CONSTRAINT FK_Subfamily_Family
        FOREIGN KEY (FamilyId) REFERENCES dbo.Family(FamilyId),
    CONSTRAINT UQ_Subfamily_Family_Code
        UNIQUE (FamilyId, Code)
);
```

### 6.4 Ensayos

Catalogo unico de ensayos. El campo `NormalizedName` permite deduplicar.

```sql
CREATE TABLE dbo.Test (
    TestId INT IDENTITY(1,1) PRIMARY KEY,
    Name NVARCHAR(300) NOT NULL,
    NormalizedName NVARCHAR(300) NOT NULL,
    IsActive BIT NOT NULL DEFAULT 1,
    CreatedAt DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
    UpdatedAt DATETIME2 NULL,
    CONSTRAINT UQ_Test_NormalizedName
        UNIQUE (NormalizedName)
);
```

### 6.5 Requisitos de ensayo por subfamilia

Relaciona cada subfamilia con sus ensayos, literales, normas y fases aplicables.

```sql
CREATE TABLE dbo.SubfamilyTestRequirement (
    RequirementId INT IDENTITY(1,1) PRIMARY KEY,
    SubfamilyId INT NOT NULL,
    TestId INT NOT NULL,
    SourceRow NVARCHAR(50) NULL,
    Literal NVARCHAR(120) NULL,
    TechnicalStandard NVARCHAR(250) NULL,
    AppliesOT BIT NOT NULL DEFAULT 0,
    AppliesVig1 BIT NOT NULL DEFAULT 0,
    AppliesVig2 BIT NOT NULL DEFAULT 0,
    AppliesRenovation BIT NOT NULL DEFAULT 0,
    IsActive BIT NOT NULL DEFAULT 1,
    CreatedAt DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
    UpdatedAt DATETIME2 NULL,
    CONSTRAINT FK_SubfamilyTestRequirement_Subfamily
        FOREIGN KEY (SubfamilyId) REFERENCES dbo.Subfamily(SubfamilyId),
    CONSTRAINT FK_SubfamilyTestRequirement_Test
        FOREIGN KEY (TestId) REFERENCES dbo.Test(TestId)
);
```

### 6.6 Combinaciones de clasificacion

Permiten conservar el modo guiado actual.

```sql
CREATE TABLE dbo.ClassificationCombination (
    ClassificationCombinationId INT IDENTITY(1,1) PRIMARY KEY,
    Code NVARCHAR(30) NOT NULL UNIQUE,
    Name NVARCHAR(500) NOT NULL,
    IsActive BIT NOT NULL DEFAULT 1,
    CreatedAt DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
    UpdatedAt DATETIME2 NULL
);

CREATE TABLE dbo.ClassificationCombinationFamily (
    ClassificationCombinationId INT NOT NULL,
    FamilyId INT NOT NULL,
    PRIMARY KEY (ClassificationCombinationId, FamilyId),
    CONSTRAINT FK_ClassificationCombinationFamily_Combination
        FOREIGN KEY (ClassificationCombinationId)
        REFERENCES dbo.ClassificationCombination(ClassificationCombinationId),
    CONSTRAINT FK_ClassificationCombinationFamily_Family
        FOREIGN KEY (FamilyId) REFERENCES dbo.Family(FamilyId)
);
```

### 6.7 Combinaciones de subfamilias

Representan las subcombinaciones disponibles dentro de cada clasificacion.

```sql
CREATE TABLE dbo.SubfamilyCombination (
    SubfamilyCombinationId INT IDENTITY(1,1) PRIMARY KEY,
    ClassificationCombinationId INT NOT NULL,
    Code NVARCHAR(40) NOT NULL UNIQUE,
    Name NVARCHAR(700) NOT NULL,
    IsActive BIT NOT NULL DEFAULT 1,
    CreatedAt DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
    UpdatedAt DATETIME2 NULL,
    CONSTRAINT FK_SubfamilyCombination_ClassificationCombination
        FOREIGN KEY (ClassificationCombinationId)
        REFERENCES dbo.ClassificationCombination(ClassificationCombinationId)
);

CREATE TABLE dbo.SubfamilyCombinationItem (
    SubfamilyCombinationId INT NOT NULL,
    SubfamilyId INT NOT NULL,
    PRIMARY KEY (SubfamilyCombinationId, SubfamilyId),
    CONSTRAINT FK_SubfamilyCombinationItem_Combination
        FOREIGN KEY (SubfamilyCombinationId)
        REFERENCES dbo.SubfamilyCombination(SubfamilyCombinationId),
    CONSTRAINT FK_SubfamilyCombinationItem_Subfamily
        FOREIGN KEY (SubfamilyId) REFERENCES dbo.Subfamily(SubfamilyId)
);
```

### 6.8 Proyectos

Representan una sesion de trabajo guardada por el usuario.

```sql
CREATE TABLE dbo.Project (
    ProjectId INT IDENTITY(1,1) PRIMARY KEY,
    Name NVARCHAR(250) NOT NULL,
    CreatedBy NVARCHAR(120) NULL,
    CreatedAt DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
    UpdatedAt DATETIME2 NULL,
    IsActive BIT NOT NULL DEFAULT 1
);
```

### 6.9 Juguetes o items del proyecto

Cada proyecto puede contener uno o varios juguetes/items.

```sql
CREATE TABLE dbo.ProjectToy (
    ProjectToyId INT IDENTITY(1,1) PRIMARY KEY,
    ProjectId INT NOT NULL,
    Name NVARCHAR(250) NOT NULL,
    CreatedAt DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
    UpdatedAt DATETIME2 NULL,
    IsActive BIT NOT NULL DEFAULT 1,
    CONSTRAINT FK_ProjectToy_Project
        FOREIGN KEY (ProjectId) REFERENCES dbo.Project(ProjectId)
);

CREATE TABLE dbo.ProjectToySubfamily (
    ProjectToyId INT NOT NULL,
    SubfamilyId INT NOT NULL,
    PRIMARY KEY (ProjectToyId, SubfamilyId),
    CONSTRAINT FK_ProjectToySubfamily_ProjectToy
        FOREIGN KEY (ProjectToyId) REFERENCES dbo.ProjectToy(ProjectToyId),
    CONSTRAINT FK_ProjectToySubfamily_Subfamily
        FOREIGN KEY (SubfamilyId) REFERENCES dbo.Subfamily(SubfamilyId)
);
```

## 7. Consulta base para calculo consolidado

El calculo debe deduplicar por ensayo normalizado y agrupar literales, normas, subfamilias, familias y juguetes.

Una vista inicial podria ser:

```sql
CREATE VIEW dbo.vw_ProjectToyRequirements
AS
SELECT
    p.ProjectId,
    p.Name AS ProjectName,
    pt.ProjectToyId,
    pt.Name AS ToyName,
    pl.ProductLineId,
    pl.Name AS ProductLineName,
    f.FamilyId,
    f.Name AS FamilyName,
    sf.SubfamilyId,
    sf.Name AS SubfamilyName,
    t.TestId,
    t.Name AS TestName,
    t.NormalizedName,
    r.SourceRow,
    r.Literal,
    r.TechnicalStandard,
    r.AppliesOT,
    r.AppliesVig1,
    r.AppliesVig2,
    r.AppliesRenovation
FROM dbo.Project p
INNER JOIN dbo.ProjectToy pt
    ON pt.ProjectId = p.ProjectId
INNER JOIN dbo.ProjectToySubfamily pts
    ON pts.ProjectToyId = pt.ProjectToyId
INNER JOIN dbo.Subfamily sf
    ON sf.SubfamilyId = pts.SubfamilyId
INNER JOIN dbo.Family f
    ON f.FamilyId = sf.FamilyId
INNER JOIN dbo.ProductLine pl
    ON pl.ProductLineId = f.ProductLineId
INNER JOIN dbo.SubfamilyTestRequirement r
    ON r.SubfamilyId = sf.SubfamilyId
INNER JOIN dbo.Test t
    ON t.TestId = r.TestId
WHERE
    p.IsActive = 1
    AND pt.IsActive = 1
    AND pl.IsActive = 1
    AND f.IsActive = 1
    AND sf.IsActive = 1
    AND r.IsActive = 1
    AND t.IsActive = 1;
```

Para el calculo final, PHP puede consultar la vista y hacer la agrupacion, o SQL Server puede entregar una consulta agregada usando `STRING_AGG`, disponible en SQL Server 2019.

Ejemplo de consulta agregada:

```sql
SELECT
    NormalizedName,
    MIN(TestName) AS TestName,
    STRING_AGG(DISTINCT Literal, ' | ') AS Literals,
    STRING_AGG(DISTINCT TechnicalStandard, ' | ') AS TechnicalStandards,
    STRING_AGG(DISTINCT SubfamilyName, ' | ') AS SourceSubfamilies,
    STRING_AGG(DISTINCT FamilyName, ' | ') AS SourceFamilies,
    STRING_AGG(DISTINCT ToyName, ' | ') AS Toys,
    MAX(CAST(AppliesOT AS INT)) AS AppliesOT,
    MAX(CAST(AppliesVig1 AS INT)) AS AppliesVig1,
    MAX(CAST(AppliesVig2 AS INT)) AS AppliesVig2,
    MAX(CAST(AppliesRenovation AS INT)) AS AppliesRenovation
FROM dbo.vw_ProjectToyRequirements
WHERE ProjectId = @ProjectId
GROUP BY NormalizedName
ORDER BY TestName;
```

Nota: si la instancia SQL Server no acepta `DISTINCT` dentro de `STRING_AGG`, se debe resolver con subconsultas previas o hacer la agrupacion en PHP.

## 8. Endpoints PHP sugeridos

### Catalogos

```text
GET /api/product-lines
GET /api/families?productLineId=1
GET /api/subfamilies?familyId=1
GET /api/classification-combinations?productLineId=1
GET /api/subfamily-combinations?classificationCombinationId=1
```

### Proyectos

```text
POST /api/projects
GET /api/projects
GET /api/projects/{id}
PUT /api/projects/{id}
DELETE /api/projects/{id}
```

### Juguetes o items

```text
POST /api/projects/{id}/toys
PUT /api/projects/{id}/toys/{toyId}
DELETE /api/projects/{id}/toys/{toyId}
```

### Calculos y exportaciones

```text
GET /api/projects/{id}/calculation
GET /api/projects/{id}/export/csv
GET /api/projects/{id}/export/json
```

## 9. Conexion PHP a SQL Server

Se recomienda usar `PDO_SQLSRV` por portabilidad y manejo ordenado de consultas preparadas.

Ejemplo base:

```php
<?php

final class Database
{
    public static function connection(): PDO
    {
        $server = getenv('DB_SERVER');
        $database = getenv('DB_DATABASE');
        $user = getenv('DB_USER');
        $password = getenv('DB_PASSWORD');

        $dsn = "sqlsrv:Server={$server};Database={$database};TrustServerCertificate=true";

        return new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}
```

Buenas practicas:

- Usar consultas preparadas.
- No concatenar parametros recibidos por HTTP en SQL.
- No versionar credenciales reales.
- Registrar errores sin exponer detalles internos al usuario final.

## 10. Seguridad y requisitos esperados por TI

Se recomienda validar con TI los siguientes puntos:

- Autenticacion integrada con el mecanismo corporativo disponible.
- Roles de usuario: consulta, editor, administrador.
- Registro de creacion y modificacion de proyectos.
- Bitacora de cambios sobre catalogos normativos.
- Respaldo periodico de SQL Server.
- Politica de retencion de proyectos.
- Separacion de ambientes: desarrollo, pruebas y produccion.
- Control de versiones del codigo y scripts SQL.
- Manejo de credenciales mediante variables de entorno o vault corporativo.
- Restriccion de acceso directo a carpetas internas del proyecto.
- Validacion de entrada en backend.
- Proteccion contra SQL Injection usando parametros.

## 11. Auditoria recomendada

Para una fase posterior, se recomienda agregar una tabla de auditoria:

```sql
CREATE TABLE dbo.AuditLog (
    AuditLogId BIGINT IDENTITY(1,1) PRIMARY KEY,
    EntityName NVARCHAR(120) NOT NULL,
    EntityId NVARCHAR(80) NULL,
    ActionName NVARCHAR(40) NOT NULL,
    OldValue NVARCHAR(MAX) NULL,
    NewValue NVARCHAR(MAX) NULL,
    CreatedBy NVARCHAR(120) NULL,
    CreatedAt DATETIME2 NOT NULL DEFAULT SYSDATETIME()
);
```

Esta tabla permitiria registrar cambios importantes en catalogos, proyectos y resultados guardados.

## 12. Indices recomendados

```sql
CREATE INDEX IX_Family_ProductLineId
ON dbo.Family(ProductLineId);

CREATE INDEX IX_Subfamily_FamilyId
ON dbo.Subfamily(FamilyId);

CREATE INDEX IX_SubfamilyTestRequirement_SubfamilyId
ON dbo.SubfamilyTestRequirement(SubfamilyId);

CREATE INDEX IX_SubfamilyTestRequirement_TestId
ON dbo.SubfamilyTestRequirement(TestId);

CREATE INDEX IX_ProjectToy_ProjectId
ON dbo.ProjectToy(ProjectId);

CREATE INDEX IX_ProjectToySubfamily_SubfamilyId
ON dbo.ProjectToySubfamily(SubfamilyId);
```

## 13. Estrategia de migracion desde el HTML actual

### Fase 1: Preparacion

- Crear repositorio estructurado.
- Crear scripts iniciales SQL.
- Crear conexion PHP a SQL Server.
- Definir ambientes con TI.

### Fase 2: Migracion de datos

- Extraer `app-data` del HTML.
- Convertir JSON a registros SQL.
- Cargar lineas, familias, subfamilias, ensayos, requisitos y combinaciones.
- Validar conteos contra la matriz actual.

Conteos esperados para el archivo actual:

- 34 combinaciones de clasificacion.
- 253 combinaciones de subfamilia.
- 33 subfamilias base.
- 14 familias.
- 44 ensayos base unicos.
- 2870 registros de ensayos unicos generados.
- 249 registros base.

### Fase 3: Backend PHP

- Crear endpoints de consulta.
- Crear endpoints de proyectos.
- Crear endpoint de calculo consolidado.
- Crear exportacion CSV/JSON.

### Fase 4: Adaptacion del frontend

- Reemplazar JSON embebido por llamadas a PHP.
- Mantener la experiencia actual del usuario.
- Cambiar `localStorage` por persistencia en base de datos.

### Fase 5: Validacion

- Comparar resultados del HTML actual contra resultados PHP/SQL.
- Probar deduplicacion.
- Probar exportaciones.
- Probar permisos y trazabilidad.
- Preparar acta o evidencia para TI.

## 14. Reglas de negocio principales

- Un proyecto puede tener varios juguetes o items.
- Un juguete puede tener varias subfamilias.
- Una subfamilia puede tener varios ensayos.
- Un ensayo puede aplicar a varias subfamilias.
- La deduplicacion se realiza por nombre normalizado del ensayo.
- Si un ensayo aparece varias veces, se muestra una sola vez y se agrupan fuentes.
- Las fases aplicables se consolidan con logica OR: si aplica en una fuente, aplica en el resultado consolidado.

## 15. Decisiones pendientes

Antes de desarrollo productivo, se deben confirmar:

- Metodo de autenticacion corporativa.
- Servidor web oficial: IIS, Apache u otro.
- Version exacta de PHP permitida.
- Driver aprobado para SQL Server: `pdo_sqlsrv` o `sqlsrv`.
- Convencion interna de nombres de tablas y campos.
- Politica de backups.
- Si los resultados calculados deben guardarse historicamente.
- Si los catalogos normativos podran ser editados por usuarios administradores.
- Si RETIE/RETILAP entraran en la misma aplicacion o en modulos separados.

## 16. Recomendacion final

La mejor ruta es migrar por etapas. Primero se debe llevar la matriz actual a SQL Server y reproducir exactamente el calculo existente. Despues se puede ampliar a productos normativos, RETIE, RETILAP u otras lineas.

Esta estrategia reduce el riesgo, facilita la revision de TI y deja el sistema preparado para crecer sin depender de un unico archivo HTML.

