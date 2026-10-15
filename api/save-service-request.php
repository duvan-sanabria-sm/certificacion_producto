<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Support/Auth.php';

$authUser = auth_require_permission('requests.create');

function redirect_request_save(string $status, string $message, array $extra = []): void
{
    header('Location: ../index.php?' . http_build_query(array_merge([
        'request_status' => $status,
        'request_message' => $message,
    ], $extra)));
    exit;
}

function request_save_value(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_request_save('error', 'Metodo no permitido.');
}

try {
    auth_assert_csrf();
    $pdo = db();
    $requestId = (int) ($_POST['solicitud_id'] ?? 0);
    $currentRequest = null;

    if ($requestId > 0) {
        if (!auth_can('requests.edit', $authUser)) {
            throw new RuntimeException('Tu perfil no puede editar solicitudes.');
        }
        $statement = $pdo->prepare('SELECT * FROM solicitudes_servicio WHERE id = :id');
        $statement->execute(['id' => $requestId]);
        $currentRequest = $statement->fetch();

        if (!$currentRequest) {
            throw new RuntimeException('La solicitud que intentas editar no existe.');
        }
        if (!auth_can_access_request($pdo, $requestId)) {
            throw new RuntimeException('No tienes acceso a esta solicitud.');
        }
    }

    $assignedCommercialId = (int) ($currentRequest['comercial_id'] ?? $authUser['comercial_id'] ?? 0);
    if (($authUser['rol'] ?? '') === 'comercial_1' && $assignedCommercialId <= 0) {
        throw new RuntimeException('Tu usuario Comercial 1 debe estar asociado a un comercial antes de registrar solicitudes.');
    }

    $pdo->beginTransaction();

    $clienteNombre = request_save_value('cliente_nombre');
    $tipoDocumento = request_save_value('tipo_documento') ?: 'NIT';
    $numeroDocumento = request_save_value('numero_documento');
    $direccion = request_save_value('direccion');
    $ciudad = request_save_value('ciudad');
    $contactoNombre = request_save_value('contacto_nombre');
    $contactoCargo = request_save_value('contacto_cargo');
    $contactoCorreo = request_save_value('contacto_correo');
    $contactoTelefono = request_save_value('contacto_telefono');
    $lineaProductoId = (int) ($_POST['linea_producto_id'] ?? 0);
    $codigoAnterior = trim((string) ($currentRequest['codigo_solicitud'] ?? ''));
    $codigoSolicitud = auth_can('requests.sequence.manage', $authUser)
        ? strtoupper(request_save_value('codigo_solicitud'))
        : $codigoAnterior;
    $servicioSolicitado = request_save_value('servicio_solicitado');
    $producto = request_save_value('producto');
    $referenciaProducto = request_save_value('referencia_producto');
    $cantidadMuestras = request_save_value('cantidad_muestras');
    $reglamentoNorma = request_save_value('reglamento_norma');
    $observaciones = request_save_value('observaciones');

    if ($codigoSolicitud !== '' && !preg_match('/^[A-Z0-9][A-Z0-9._-]{2,79}$/', $codigoSolicitud)) {
        throw new RuntimeException('El consecutivo solo puede contener letras, números, punto, guion y guion bajo.');
    }
    $duplicateStatement = $pdo->prepare(
        'SELECT COUNT(*) FROM solicitudes_servicio
         WHERE codigo_solicitud = :codigo AND id <> :solicitud_id'
    );
    if ($codigoSolicitud !== '') {
        $duplicateStatement->execute(['codigo' => $codigoSolicitud, 'solicitud_id' => $requestId]);
        if ((int) $duplicateStatement->fetchColumn() > 0) {
            throw new RuntimeException('El consecutivo indicado ya pertenece a otra solicitud.');
        }
    }

    $clienteId = (int) ($currentRequest['cliente_id'] ?? 0) ?: null;
    if ($clienteNombre !== '') {
        if ($clienteId) {
            $clientStatement = $pdo->prepare(
                'UPDATE clientes
                 SET tipo_documento = :tipo_documento,
                     numero_documento = :numero_documento,
                     razon_social = :razon_social,
                     contacto_nombre = :contacto_nombre,
                     contacto_correo = :contacto_correo,
                     contacto_telefono = :contacto_telefono,
                     direccion = :direccion,
                     ciudad = :ciudad
                 WHERE id = :id'
            );
            $clientStatement->execute([
                'id' => $clienteId,
                'tipo_documento' => $tipoDocumento ?: null,
                'numero_documento' => $numeroDocumento ?: null,
                'razon_social' => $clienteNombre,
                'contacto_nombre' => $contactoNombre ?: null,
                'contacto_correo' => $contactoCorreo ?: null,
                'contacto_telefono' => $contactoTelefono ?: null,
                'direccion' => $direccion ?: null,
                'ciudad' => $ciudad ?: null,
            ]);
        } else {
            $clientSql = db_is_postgres($pdo)
                ? 'INSERT INTO clientes
                    (tipo_documento, numero_documento, razon_social, contacto_nombre, contacto_correo, contacto_telefono, direccion, ciudad)
                   VALUES
                    (:tipo_documento, :numero_documento, :razon_social, :contacto_nombre, :contacto_correo, :contacto_telefono, :direccion, :ciudad)
                   ON CONFLICT (tipo_documento, numero_documento) DO UPDATE SET
                    razon_social = EXCLUDED.razon_social,
                    contacto_nombre = EXCLUDED.contacto_nombre,
                    contacto_correo = EXCLUDED.contacto_correo,
                    contacto_telefono = EXCLUDED.contacto_telefono,
                    direccion = EXCLUDED.direccion,
                    ciudad = EXCLUDED.ciudad
                   RETURNING id'
                : 'INSERT INTO clientes
                    (tipo_documento, numero_documento, razon_social, contacto_nombre, contacto_correo, contacto_telefono, direccion, ciudad)
                   VALUES
                    (:tipo_documento, :numero_documento, :razon_social, :contacto_nombre, :contacto_correo, :contacto_telefono, :direccion, :ciudad)
                   ON DUPLICATE KEY UPDATE
                    razon_social = VALUES(razon_social),
                    contacto_nombre = VALUES(contacto_nombre),
                    contacto_correo = VALUES(contacto_correo),
                    contacto_telefono = VALUES(contacto_telefono),
                    direccion = VALUES(direccion),
                    ciudad = VALUES(ciudad),
                    id = LAST_INSERT_ID(id)';
            $clientStatement = $pdo->prepare($clientSql);
            $clientStatement->execute([
                'tipo_documento' => $tipoDocumento ?: null,
                'numero_documento' => $numeroDocumento ?: null,
                'razon_social' => $clienteNombre,
                'contacto_nombre' => $contactoNombre ?: null,
                'contacto_correo' => $contactoCorreo ?: null,
                'contacto_telefono' => $contactoTelefono ?: null,
                'direccion' => $direccion ?: null,
                'ciudad' => $ciudad ?: null,
            ]);
            $clienteId = db_is_postgres($pdo) ? (int) $clientStatement->fetchColumn() : db_last_insert_id($pdo);
        }
    }

    $storedPreliminaryData = json_decode((string) ($currentRequest['datos_preliminares_json'] ?? ''), true);
    $preliminaryData = array_replace(is_array($storedPreliminaryData) ? $storedPreliminaryData : [], [
        'cliente_nombre' => $clienteNombre,
        'tipo_documento' => $tipoDocumento,
        'numero_documento' => $numeroDocumento,
        'direccion' => $direccion,
        'ciudad' => $ciudad,
        'contacto_nombre' => $contactoNombre,
        'contacto_cargo' => $contactoCargo,
        'contacto_correo' => $contactoCorreo,
        'contacto_telefono' => $contactoTelefono,
        'producto' => $producto,
        'referencia_producto' => $referenciaProducto,
        'cantidad_muestras' => $cantidadMuestras,
        'reglamento_norma' => $reglamentoNorma,
        'observaciones' => $observaciones,
    ]);
    unset($preliminaryData['origenes_gmv_rg20']);

    if ($requestId > 0) {
        $requestStatement = $pdo->prepare(
            'UPDATE solicitudes_servicio
             SET cliente_id = :cliente_id,
                 linea_producto_id = :linea_producto_id,
                 codigo_solicitud = :codigo_solicitud,
                 servicio_solicitado = :servicio_solicitado,
                 producto = :producto,
                 referencia_producto = :referencia_producto,
                 cantidad_muestras = :cantidad_muestras,
                 reglamento_norma = :reglamento_norma,
                 datos_preliminares_json = :datos_preliminares_json,
                 observaciones = :observaciones
             WHERE id = :id'
        );
        $requestStatement->execute([
            'id' => $requestId,
            'cliente_id' => $clienteId ?: null,
            'linea_producto_id' => $lineaProductoId > 0 ? $lineaProductoId : null,
            'codigo_solicitud' => $codigoSolicitud ?: null,
            'servicio_solicitado' => $servicioSolicitado ?: null,
            'producto' => $producto ?: null,
            'referencia_producto' => $referenciaProducto ?: null,
            'cantidad_muestras' => $cantidadMuestras ?: null,
            'reglamento_norma' => $reglamentoNorma ?: null,
            'datos_preliminares_json' => json_encode($preliminaryData, JSON_UNESCAPED_UNICODE),
            'observaciones' => $observaciones ?: null,
        ]);
        $message = 'Solicitud actualizada correctamente.';
    } else {
        $requestStatement = $pdo->prepare(
            'INSERT INTO solicitudes_servicio
              (cliente_id, linea_producto_id, comercial_id, canal_entrada,
               atendido_por_comercial, codigo_solicitud, servicio_solicitado, producto, referencia_producto,
               cantidad_muestras, reglamento_norma, datos_preliminares_json, observaciones, creado_por)
             VALUES
              (:cliente_id, :linea_producto_id, :comercial_id, :canal_entrada,
               :atendido_por_comercial, :codigo_solicitud, :servicio_solicitado, :producto, :referencia_producto,
               :cantidad_muestras, :reglamento_norma, :datos_preliminares_json, :observaciones, :creado_por)'
        );
        $requestStatement->execute([
            'cliente_id' => $clienteId ?: null,
            'linea_producto_id' => $lineaProductoId > 0 ? $lineaProductoId : null,
            'comercial_id' => $assignedCommercialId > 0 ? $assignedCommercialId : null,
            'canal_entrada' => 'interno',
            'atendido_por_comercial' => $assignedCommercialId > 0 ? 1 : 0,
            'codigo_solicitud' => $codigoSolicitud ?: null,
            'servicio_solicitado' => $servicioSolicitado ?: null,
            'producto' => $producto ?: null,
            'referencia_producto' => $referenciaProducto ?: null,
            'cantidad_muestras' => $cantidadMuestras ?: null,
            'reglamento_norma' => $reglamentoNorma ?: null,
            'datos_preliminares_json' => json_encode($preliminaryData, JSON_UNESCAPED_UNICODE),
            'observaciones' => $observaciones ?: null,
            'creado_por' => $authUser['usuario'],
        ]);
        $requestId = db_last_insert_id($pdo);
        if ($codigoSolicitud === '') {
            $codigoSolicitud = sprintf('SS-%s-%06d', date('Y'), $requestId);
            $duplicateStatement->execute(['codigo' => $codigoSolicitud, 'solicitud_id' => $requestId]);
            if ((int) $duplicateStatement->fetchColumn() > 0) {
                throw new RuntimeException('No fue posible asignar el consecutivo automático. Solicita al administrador corregirlo.');
            }
            $pdo->prepare('UPDATE solicitudes_servicio SET codigo_solicitud = :codigo WHERE id = :id')
                ->execute(['codigo' => $codigoSolicitud, 'id' => $requestId]);
        }
        $message = 'Solicitud registrada para identificacion preliminar.';
    }

    $pdo->commit();
    auth_log('solicitudes_servicio', (string) $requestId, $currentRequest ? 'actualizar' : 'crear', [
        'codigo_anterior' => $codigoAnterior ?: null,
        'codigo_nuevo' => $codigoSolicitud,
    ]);
    redirect_request_save('ok', $message, ['edit_request_id' => $requestId]);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    redirect_request_save('error', $exception->getMessage());
}
