# Servimeters — Certificación de producto

Aplicación web interna para administrar solicitudes, matrices normativas, laboratorios, revisión técnica, pre-cotización y cotización final del proceso de certificación de producto.

Está desarrollada en PHP nativo, CSS y JavaScript sin proceso de compilación frontend. El analizador documental usa Python en un entorno virtual aislado. En desarrollo admite MySQL/MariaDB y para sandbox/producción se entrega con PostgreSQL y contenedor Docker.

## Archivo principal

- `index.php`: panel de solicitudes y entrada al flujo interno.
- `app/Support/UI.php`: navegación lateral común y filtrada por rol.
- `constructor_iso_juguetes.php`: constructor normativo autocontenido para juguetes.

El despliegue institucional y la respuesta a los requisitos de TIC están documentados en `docs/despliegue_servimeters.md` y `docs/respuesta_requisitos_tic.md`.

## Cómo usarlo

1. Abrir `http://localhost/Software%20producto/login.php` en un navegador moderno, con Apache/XAMPP activo.
2. Descargar la plantilla oficial desde la pantalla inicial.
3. Cargar una matriz actualizada por línea de producto.
4. Adjuntar una solicitud de servicio y registrar la identificación preliminar.
5. Abrir el constructor normativo actual desde el acceso principal.

El constructor anterior sigue disponible en:

```text
http://localhost/Software%20producto/constructor_iso_juguetes.php
```

## Conexión a base de datos

La conexión PHP está en:

```text
config/database.php
```

La configuración se toma de variables de entorno. Para desarrollo local se puede copiar `.env.example` como `.env.local` y ajustar los valores. El archivo local no se versiona.

Configuración habitual para XAMPP:

- Servidor: `127.0.0.1`
- Base de datos: `software_producto`
- Usuario: `root`
- Contraseña: vacía

Para crear la base de datos:

1. Abrir `http://localhost/phpmyadmin`.
2. Ir a **Importar**.
3. Seleccionar el archivo `database/schema.sql`.
4. Ejecutar la importación.

Para PostgreSQL se usa `database/postgresql_schema.sql`; el comando portable es:

```text
php tools/apply_schema.php
```

Para probar la conexión:

```text
http://localhost/Software%20producto/api/test-db.php
```

Si la conexión funciona, verás una respuesta JSON con `ok: true`.

## Usuarios y roles

Las pantallas internas requieren autenticación. El formulario `solicitud_cliente.php` continúa siendo público para que los clientes registren solicitudes.

Para instalar la tabla de usuarios y crear el primer administrador:

```text
C:\xampp\php\php.exe tools\apply_auth_migration.php
```

El comando muestra una contraseña temporal una sola vez. El administrador debe cambiarla durante el primer ingreso y luego puede crear las demás cuentas desde:

```text
http://localhost/Software%20producto/usuarios.php
```

Perfiles disponibles:

- Administrador del software: acceso total, usuarios, criterios y prueba de base de datos.
- Comercial 1: únicamente sus solicitudes asignadas y sus pre-cotizaciones; requiere asociación con un registro de `comerciales`.
- Comercial 2: flujo comercial completo, eliminación de solicitudes, aprobación de pre-cotizaciones y cotización final.
- Coordinador técnico: solicitudes en consulta, revisión técnica, matrices y laboratorios en modo consulta.
- Jefe técnico: revisión técnica, cotización final y administración de matrices y laboratorios.

La matriz central de permisos está en `app/Support/Auth.php`. Las páginas y los endpoints aplican la misma autorización; las opciones ocultas en el menú no son el único control.

## Pantalla inicial

Archivo principal del sistema:

```text
index.php
```

Incluye:

- Estado general de la base de datos.
- Carga de matrices normativas.
- Descarga de la plantilla oficial.
- Registro preliminar de solicitudes de servicio.
- Acceso al constructor ISO actual.
- Acceso al constructor normativo ampliado.

## Solicitud web asistida para clientes

Formulario publico local:

```text
http://localhost/Software%20producto/solicitud_cliente.php
```

La solicitud se divide en cuatro fases compactas:

- Informacion del solicitante y de la organizacion representante, cuando aplique.
- Caracteristicas del servicio, con reglamento y esquema exacto.
- Identificacion manual o asistida de productos por familia, subfamilia, articulo y subnumeral.
- Asignacion comercial y envio.

Antes del envío se muestra un checklist automático de documentos. La ficha técnica continúa siendo obligatoria por producto; la fotografía, el certificado ISO 9001 y los Test Reports son soportes opcionales. Los archivos ISO y los reportes aportados quedan asociados a la solicitud para su revisión posterior. El cliente también debe responder **Sí** o **No** sobre la disponibilidad de medios tecnológicos para actividades remotas.

El formulario usa el catalogo interno de alcances y productos. Los nombres se muestran sin rayas al piso y los esquemas se conservan por reglamento; por ejemplo, `Esquema 5`, `Esquema 5 RETIE` y `Esquema 5 RETILAP` son valores diferentes.

En el paso Servicio, el proposito depende del esquema. Los esquemas 1A y 1B solo permiten otorgamiento; los esquemas 4 y 5 permiten otorgamiento, seguimiento 1, seguimiento 2 y renovacion. La combinacion entre esquema y estado ISO 9001 genera una recomendacion que tambien queda almacenada en la solicitud.

El punto de informacion junto al esquema muestra su aplicacion, vigencia, seguimientos y condiciones. En Productos, RETIE incorpora opciones tecnicas de subfamilia basadas en la Tabla 4.2.2.a. La seccion Informacion extraida cambia por alcance: electricos, juguetes, vajillas, barras y mallas, o cascos, manteniendo campos comunes y trazabilidad para todos.

La importacion actual contiene 177 categorias consolidadas en 13 alcances: RETIE, RETILAP, voluntarios, juguetes Colombia y Ecuador, vajillas, cascos, barras, alambres y mallas, RTE INEN 057, RTE INEN 069 y RTS 29. Para importar una version actualizada:

```text
C:\xampp\php\php.exe tools\import_request_catalog.php "C:\ruta\solicitud.xlsx"
```

El analizador Python esta en:

```text
python/product_analyzer.py
```

Procesa localmente texto de PDF, DOCX y XLSX, extrae campos tecnicos y compara el producto con el catalogo oficial. Cada producto debe aportar una ficha técnica; la fotografía es complementaria. El endpoint de analisis es:

```text
api/analyze-product.php
```

Para habilitar reconocimiento visual del objeto y de la etiqueta con Gemini, configura estas variables para Apache/XAMPP y reinicia Apache:

```text
SERVIMETERS_GEMINI_API_KEY=tu_clave
SERVIMETERS_GEMINI_MODEL=gemini-2.5-flash
```

Sin la clave, la aplicacion sigue funcionando con el nombre y la lectura documental local, pero no afirma haber reconocido visualmente un objeto sin texto.

Las solicitudes creadas desde el formulario se guardan en `solicitudes_servicio` y se relacionan con:

- `solicitud_productos`
- `solicitud_fabricas`
- `solicitud_adjuntos`
- `comerciales`
- `notificaciones_correo`

La vista interna de cada registro esta disponible en:

```text
http://localhost/Software%20producto/solicitud_detalle.php?id=ID_SOLICITUD
```

## Correo SMTP

El envio utiliza SMTP con TLS y queda desactivado mientras no existan credenciales. En ese estado, la solicitud se guarda normalmente y la notificacion queda como `pendiente_configuracion`.

Variables requeridas para Apache/XAMPP:

```text
SERVIMETERS_SMTP_ENABLED=true
SERVIMETERS_SMTP_HOST=smtp.office365.com
SERVIMETERS_SMTP_PORT=587
SERVIMETERS_SMTP_ENCRYPTION=tls
SERVIMETERS_SMTP_USERNAME=cuenta@servimeters.com
SERVIMETERS_SMTP_PASSWORD=clave_de_aplicacion_o_smtp
SERVIMETERS_SMTP_FROM_EMAIL=cuenta@servimeters.com
SERVIMETERS_SMTP_FROM_NAME=Servimeters - Solicitudes de producto
```

Despues de configurar las variables, reinicia Apache. Si el cliente selecciona un comercial, el correo se dirige a ese comercial; si no, se asigna al jefe comercial activo.

Las solicitudes internas se registran y corrigen directamente en el formulario web. Ya no es necesario adjuntar una solicitud de Excel para completar sus campos.

## Revisión técnica y tiempos de evaluación

El flujo interno se ejecuta en este orden:

1. Solicitud de servicio.
2. Revisión técnica y tiempos de evaluación.
3. Pre-cotización.
4. Cotización final.

La revisión técnica está disponible en:

```text
http://localhost/Software%20producto/revision_tecnica.php
```

Incluye checklist documental, validación del alcance, suficiencia de Test Reports, definición del tipo de auditoría, decisión de viabilidad y cálculo de días para evaluación inicial, sistema de gestión, laboratorio, atestiguamiento, análisis de resultados y comité. El proceso siempre conserva actividades de evaluación aunque no requiera ensayos nuevos.

El analizador de certificados está en:

```text
python/iso_certificate_analyzer.py
```

Extrae titular, norma, vigencia, certificador, acreditador y alcance del ISO 9001. Compara el alcance con los productos y fabricantes de la solicitud y genera alertas por vencimiento, titular diferente o alcance no compatible. Con `SERVIMETERS_GEMINI_API_KEY` usa análisis visual; sin la clave mantiene una validación documental local que debe confirmarse manualmente.

La pre-cotización solo permite solicitudes cuya revisión técnica haya sido aprobada. Los días calculados se trasladan como cantidades iniciales de los servicios de certificación. Comercial 2 o Administración aprueban la pre-cotización para habilitarla en la fase de cotización final. Para instalar la tabla correspondiente:

```text
C:\xampp\php\php.exe tools\apply_review_migration.php
```

## Consulta de matrices y plantillas

Pantalla agregada:

```text
matrices.php
```

Permite:

- Ver plantillas oficiales por alcance.
- Descargar matriz base desde el código inicial.
- Ver matrices importadas en la base de datos.
- Descargar matrices importadas.

Alcances base disponibles:

- JUGUETES: 249 registros base desde `constructor_iso_juguetes.php`.
- VAJILLAS: 34 registros base desde `preview (28).php`.
- RETIE: 106 registros normativos base desde `preview (28).php`.
- RETILAP: 39 registros normativos base desde `preview (28).php`.

Endpoints de descarga:

```text
api/download-template.php?scope=JUGUETES
api/download-embedded-matrix.php?scope=JUGUETES
api/download-db-matrix.php?id=1
```

## Plantilla oficial de matrices

Archivo generado:

```text
templates/plantilla_matriz_normativa.xlsx
```

La estructura oficial de columnas se mantiene en:

```text
config/matrix_template.php
```

Si se modifica esa estructura, se puede regenerar el Excel ejecutando:

```text
C:\xampp\php\php.exe tools/generate_matrix_template.php
```

Y validar la plantilla con:

```text
C:\xampp\php\php.exe tools/validate_matrix_template.php
```

## Carga de archivos

Endpoints agregados:

```text
api/upload-matrix.php
api/upload-service-request.php
```

Los archivos cargados se guardan en:

```text
storage/uploads/matrices
storage/uploads/solicitudes
```

La matriz importada se registra en las tablas:

```text
matrices
matriz_ensayos
archivos_cargados
```

La solicitud de servicio se registra en:

```text
clientes
solicitudes_servicio
archivos_cargados
```

## Funcionalidades

- Selección guiada por combinación de clasificación ISO.
- Selección manual por familia y subfamilia.
- Construcción de un juguete actual con múltiples subfamilias.
- Guardado de varios juguetes dentro del navegador.
- Cálculo consolidado de ensayos únicos para todo el lote.
- Agrupación de literales, normas, fases, fuentes y juguetes asociados.
- Filtros por texto, fase y juguete.
- Exportación a CSV.
- Exportación a JSON.
- Impresión o generación de PDF desde el navegador.
- Persistencia local mediante `localStorage`.

## Datos incluidos

El archivo principal trae los datos embebidos en un bloque JSON con id `app-data`.

Resumen de la matriz incluida:

- 34 combinaciones de clasificación.
- 253 combinaciones de subfamilia.
- 33 subfamilias base.
- 14 familias.
- 44 ensayos base únicos.
- 2870 registros de ensayos únicos generados.
- 249 registros base.

Las fases manejadas son:

- `OT`: Otorgamiento.
- `VIG_1`: Vigilancia 1.
- `VIG_2`: Vigilancia 2.
- `RENOVACION`: Renovación.

## Regla de deduplicación

El cálculo elimina duplicados por nombre normalizado del ensayo. Si el mismo ensayo aparece en varias subfamilias o juguetes, se conserva una sola fila y se agrupan:

- Literales o numerales aplicables.
- Normas técnicas.
- Subfamilias fuente.
- Familias fuente.
- Filas fuente.
- Juguetes donde aplica.
- Fases aplicables.

Esto permite obtener una matriz final más limpia para planeación de ensayos, evitando repetir pruebas que ya están cubiertas por otra subfamilia.

## Persistencia

La aplicación guarda el estado en el navegador usando la clave:

```text
constructor_iso_juguetes_v1
```

Esto incluye:

- Juguete actual.
- Juguetes guardados.

Al usar **Limpiar proyecto**, se eliminan los datos guardados en `localStorage` y se reinicia el constructor.

## Exportaciones

### CSV

Genera el archivo:

```text
ensayos_sin_repetir.csv
```

Incluye las columnas principales del resultado visible, respetando los filtros activos.

### JSON

Genera el archivo:

```text
ensayos_sin_repetir.json
```

Incluye:

- Fecha de generación.
- Metadatos de la matriz.
- Métricas del cálculo.
- Juguetes evaluados.
- Resultados exportados.

## Estructura técnica

La aplicación está construida con PHP y tecnologías nativas del navegador:

- PHP como contenedor de la página servida por XAMPP.
- HTML para la estructura.
- CSS embebido para la interfaz.
- JavaScript puro para la lógica.
- JSON embebido para la matriz de datos.
- `localStorage` para persistencia local.
- `Blob` y enlaces temporales para exportar archivos.
- `window.print()` para imprimir o guardar como PDF.

No usa React, Vue, Angular, Bootstrap, Tailwind ni librerías externas.

## Archivos relacionados

- `preview (27).php`: variante generalizada del constructor para productos/artículos normativos.
- `preview (28).php`: variante más amplia con líneas de producto, productos normativos y referencias RETIE/RETILAP.
- `SP-OP-36 V8 Lista de Chequeo requisitos exigidos RETIE 2024.pdf`: documento PDF relacionado con RETIE.
- `SP-OP-38 V8 4 Lista de Chequeo RETILAP 2024.pdf`: documento PDF relacionado con RETILAP.

El archivo activo `constructor_iso_juguetes.php` se enfoca específicamente en juguetes y ensayos asociados a la matriz ISO/Resolución 0686.

## Mantenimiento

Para modificar la matriz de datos, se debe actualizar el JSON embebido dentro del script:

```html
<script type="application/json" id="app-data">
```

Las estructuras principales son:

- `meta`: metadatos y estadísticas.
- `subfamilies`: familias y subfamilias disponibles.
- `tests`: ensayos base asociados a subfamilias.
- `combinations`: combinaciones de clasificación y subfamilias.

Al cambiar nombres de subfamilias o familias, se debe conservar consistencia entre los campos normalizados usados como llaves:

- `familiaNorm`
- `subfamiliaNorm`
- `classFamilies`
- `subfamilies`

## Limitaciones actuales

- Las matrices del constructor legado todavía conservan parte de sus datos embebidos en PHP y su estado local en el navegador.
- El cálculo depende de la calidad y consistencia de los nombres de ensayo para deduplicar correctamente.
- Los PDFs normativos no son leídos automáticamente por la aplicación principal.
- El reconocimiento visual avanzado requiere `SERVIMETERS_GEMINI_API_KEY`; sin ella se usa clasificacion documental local.
- El OCR local de fotografias requiere Tesseract instalado en el equipo; no es necesario cuando Gemini Vision esta habilitado.
