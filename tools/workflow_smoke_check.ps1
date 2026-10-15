$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$php = 'C:\xampp\php\php.exe'
$mysql = 'C:\xampp\mysql\bin\mysql.exe'
$baseUrl = 'http://localhost/Software%20producto/'

function Assert-True([bool] $condition, [string] $message) {
    if (-not $condition) {
        throw $message
    }
    Write-Output ("OK: {0}" -f $message)
}

function Invoke-MySqlScalar([string] $query) {
    return (& $mysql --user=root --database=software_producto --batch --skip-column-names --execute=$query | Select-Object -First 1)
}

function Open-TestSession([string] $username, [string] $password) {
    $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $login = Invoke-WebRequest -Uri ($baseUrl + 'login.php') -WebSession $session -UseBasicParsing -TimeoutSec 15
    $csrf = [regex]::Match($login.Content, 'name="csrf_token" value="([^"]+)"').Groups[1].Value
    Assert-True ($csrf -ne '') 'El formulario de acceso entrega CSRF'
    $page = Invoke-WebRequest -Uri ($baseUrl + 'login.php') -Method Post -Body @{
        csrf_token = $csrf
        usuario = $username
        password = $password
        next = ''
    } -WebSession $session -UseBasicParsing -TimeoutSec 15
    return [pscustomobject]@{ Session = $session; Csrf = $csrf; Page = $page }
}

Push-Location $projectRoot
$fixture = $null
try {
    $fixture = (& $php tools/auth_test_fixture.php setup | ConvertFrom-Json)
    $ownId = [int](Invoke-MySqlScalar "SELECT id FROM solicitudes_servicio WHERE codigo_solicitud='AUTH-TEST-OWN' LIMIT 1")
    Assert-True ($ownId -gt 0) 'La solicitud temporal fue creada'

    $admin = Open-TestSession $fixture.users.administrador $fixture.password
    Assert-True (-not $admin.Page.Content.Contains('GMV-RG-20')) 'La interfaz ya no muestra GMV-RG-20'
    Assert-True (-not $admin.Page.Content.Contains('Actualizar matriz normativa')) 'La interfaz ya no muestra Actualizar matriz normativa'
    Assert-True (-not $admin.Page.Content.Contains('requestFile')) 'La interfaz ya no solicita el archivo antiguo'

    $detail = Invoke-WebRequest -Uri ($baseUrl + "solicitud_detalle.php?id=$ownId") -WebSession $admin.Session -UseBasicParsing -TimeoutSec 15
    Assert-True ($detail.Content.Contains('Editar solicitud')) 'El detalle permite editar la solicitud'

    Invoke-WebRequest -Uri ($baseUrl + 'api/save-service-request.php') -Method Post -Body @{
        csrf_token = $admin.Csrf
        solicitud_id = $ownId
        codigo_solicitud = 'AUTH-TEST-RENAMED'
        tipo_documento = 'NIT'
        producto = 'Producto temporal editado'
    } -WebSession $admin.Session -UseBasicParsing -TimeoutSec 15 | Out-Null
    Assert-True ((Invoke-MySqlScalar "SELECT codigo_solicitud FROM solicitudes_servicio WHERE id=$ownId") -eq 'AUTH-TEST-RENAMED') 'El administrador puede editar el consecutivo'

    $commercial = Open-TestSession $fixture.users.comercial $fixture.password
    Invoke-WebRequest -Uri ($baseUrl + 'api/save-service-request.php') -Method Post -Body @{
        csrf_token = $commercial.Csrf
        solicitud_id = $ownId
        codigo_solicitud = 'AUTH-TEST-NO-AUTORIZADO'
        tipo_documento = 'NIT'
        producto = 'Producto temporal comercial'
    } -WebSession $commercial.Session -UseBasicParsing -TimeoutSec 15 | Out-Null
    Assert-True ((Invoke-MySqlScalar "SELECT codigo_solicitud FROM solicitudes_servicio WHERE id=$ownId") -eq 'AUTH-TEST-RENAMED') 'Un usuario no administrador no puede alterar el consecutivo'

    $duplicate = Invoke-WebRequest -Uri ($baseUrl + 'api/save-service-request.php') -Method Post -Body @{
        csrf_token = $admin.Csrf
        solicitud_id = $ownId
        codigo_solicitud = 'AUTH-TEST-OTHER'
        tipo_documento = 'NIT'
        producto = 'Producto temporal editado'
    } -WebSession $admin.Session -UseBasicParsing -TimeoutSec 15
    Assert-True ($duplicate.Content.Contains('ya pertenece a otra solicitud')) 'Los consecutivos duplicados son rechazados'

    Invoke-WebRequest -Uri ($baseUrl + 'api/save-prequote.php') -Method Post -Body @{
        csrf_token = $admin.Csrf
        precotizacion_id = ''
        solicitud_id = $ownId
        rentabilidad_porcentaje = '10'
        request_label = 'Solicitud temporal'
        tipo_servicio = 'certificacion'
        'servicio[0]' = 'Evaluación temporal'
        'recurso[0]' = 'Profesional'
        'servicio_cantidad[0]' = '2'
        'servicio_unidad[0]' = 'horas'
        'servicio_valor_unitario[0]' = '100000'
        'servicio_tipo[0]' = 'certificacion'
        'servicio_orden[0]' = '10'
        submit_action = 'revision'
    } -WebSession $admin.Session -UseBasicParsing -TimeoutSec 15 | Out-Null

    $quoteId = [int](Invoke-MySqlScalar "SELECT id FROM precotizaciones WHERE solicitud_servicio_id=$ownId ORDER BY id DESC LIMIT 1")
    Assert-True ($quoteId -gt 0) 'La pre-cotización fue guardada'
    Assert-True ((Invoke-MySqlScalar "SELECT estado FROM precotizaciones WHERE id=$quoteId") -eq 'en_revision') 'La pre-cotización pasa a revisión técnica'

    $coordinator = Open-TestSession $fixture.users.coordinador_tecnico $fixture.password
    $reviewCoordinator = Invoke-WebRequest -Uri ($baseUrl + 'revision_tecnica.php') -WebSession $coordinator.Session -UseBasicParsing -TimeoutSec 15
    Assert-True ($reviewCoordinator.Content -match "precotizacion_id=$quoteId[^>]*>Editar<") 'El coordinador puede editar en revisión'
    Assert-True ($reviewCoordinator.Content -match "precotizacion_id=$quoteId&amp;print=1") 'El coordinador puede imprimir en revisión'
    Assert-True (-not $reviewCoordinator.Content.Contains('api/update-prequote-status.php')) 'El coordinador no puede aprobar'

    $printPage = Invoke-WebRequest -Uri ($baseUrl + "pre_cotizacion.php?precotizacion_id=$quoteId&print=1") -WebSession $coordinator.Session -UseBasicParsing -TimeoutSec 15
    Assert-True ($printPage.Content.Contains('data-auto-print="1"') -and $printPage.Content.Contains('print-quote')) 'La vista de impresión carga la pre-cotización guardada'

    $chief = Open-TestSession $fixture.users.jefe_tecnico $fixture.password
    $reviewChief = Invoke-WebRequest -Uri ($baseUrl + 'revision_tecnica.php') -WebSession $chief.Session -UseBasicParsing -TimeoutSec 15
    Assert-True ($reviewChief.Content.Contains('api/update-prequote-status.php')) 'El jefe técnico puede aprobar'

    Invoke-WebRequest -Uri ($baseUrl + 'api/update-prequote-status.php') -Method Post -Body @{
        csrf_token = $chief.Csrf
        precotizacion_id = $quoteId
    } -WebSession $chief.Session -UseBasicParsing -TimeoutSec 15 | Out-Null
    Assert-True ((Invoke-MySqlScalar "SELECT estado FROM precotizaciones WHERE id=$quoteId") -eq 'aprobada') 'La aprobación actualiza el estado'

    $locked = Invoke-WebRequest -Uri ($baseUrl + "pre_cotizacion.php?precotizacion_id=$quoteId") -WebSession $chief.Session -UseBasicParsing -TimeoutSec 15
    Assert-True ($locked.Content.Contains('permanece bloqueada')) 'La pre-cotización aprobada queda bloqueada'
    $finalQuote = Invoke-WebRequest -Uri ($baseUrl + 'cotizacion_final.php') -WebSession $chief.Session -UseBasicParsing -TimeoutSec 15
    $quoteCode = Invoke-MySqlScalar "SELECT codigo FROM precotizaciones WHERE id=$quoteId"
    Assert-True ($finalQuote.Content.Contains($quoteCode)) 'La fase final muestra únicamente la cotización aprobada'
}
finally {
    if ($fixture) {
        & $php tools/auth_test_fixture.php cleanup | Out-Null
    }
    Pop-Location
}

Write-Output 'WORKFLOW_SMOKE_CHECK=OK'
