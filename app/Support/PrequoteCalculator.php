<?php
declare(strict_types=1);

require_once __DIR__ . '/MatrixTemplate.php';
require_once __DIR__ . '/EmbeddedMatrix.php';

function margin_value(float $cost, float $marginPercent): float
{
    $margin = max(min($marginPercent, 95), 0);
    return $margin >= 95 ? $cost : ($cost / (1 - ($margin / 100)));
}

function find_line_code_by_id(PDO $pdo, ?int $lineId): ?string
{
    if (!$lineId) {
        return null;
    }

    $statement = $pdo->prepare('SELECT codigo FROM lineas_producto WHERE id = :id');
    $statement->execute(['id' => $lineId]);
    $code = $statement->fetchColumn();

    return $code ? (string) $code : null;
}

function request_required_tests(PDO $pdo, array $request): array
{
    $lineId = (int) ($request['linea_producto_id'] ?? 0);
    $product = trim((string) ($request['producto'] ?? ''));
    $reference = trim((string) ($request['referencia_producto'] ?? ''));
    $regulation = trim((string) ($request['reglamento_norma'] ?? ''));

    $rows = [];
    if ($lineId > 0) {
        $filters = [];
        $params = ['linea_id' => $lineId];
        $like = db_like_operator($pdo);

        if ($product !== '') {
            $filters[] = "(me.producto {$like} :product_name OR me.familia {$like} :product_family OR me.subfamilia {$like} :product_subfamily OR :product_text {$like} CONCAT('%', me.producto, '%'))";
            $params['product_name'] = '%' . $product . '%';
            $params['product_family'] = '%' . $product . '%';
            $params['product_subfamily'] = '%' . $product . '%';
            $params['product_text'] = $product;
        }

        if ($regulation !== '') {
            $filters[] = "(me.reglamento_norma {$like} :regulation_name OR me.norma_tecnica {$like} :regulation_standard)";
            $params['regulation_name'] = '%' . $regulation . '%';
            $params['regulation_standard'] = '%' . $regulation . '%';
        }

        $where = $filters ? ' AND ' . implode(' AND ', $filters) : '';
        $statement = $pdo->prepare(
            "SELECT DISTINCT me.ensayo, me.norma_tecnica, me.producto, me.familia, me.subfamilia
             FROM matriz_ensayos me
             INNER JOIN matrices m ON m.id = me.matriz_id
             WHERE me.linea_producto_id = :linea_id
               AND m.estado = 'activa'
               {$where}
             ORDER BY me.ensayo"
        );
        $statement->execute($params);
        $rows = $statement->fetchAll();

        if (!$rows && $filters) {
            $statement = $pdo->prepare(
                "SELECT DISTINCT ensayo, norma_tecnica, producto, familia, subfamilia
                 FROM matriz_ensayos
                 WHERE linea_producto_id = :linea_id
                 ORDER BY ensayo"
            );
            $statement->execute(['linea_id' => $lineId]);
            $rows = $statement->fetchAll();
        } elseif (!$rows) {
            $statement = $pdo->prepare(
                "SELECT DISTINCT ensayo, norma_tecnica, producto, familia, subfamilia
                 FROM matriz_ensayos
                 WHERE linea_producto_id = :linea_id
                 ORDER BY ensayo"
            );
            $statement->execute(['linea_id' => $lineId]);
            $rows = $statement->fetchAll();
        }
    }

    if (!$rows) {
        $lineCode = find_line_code_by_id($pdo, $lineId);
        if (!$lineCode) {
            $lineCode = detect_product_line_from_text($regulation . ' ' . $product . ' ' . $reference);
        }

        if ($lineCode) {
            $rows = array_map(fn ($row) => [
                'ensayo' => $row['ensayo'] ?? '',
                'norma_tecnica' => $row['norma_tecnica'] ?? '',
                'producto' => $row['producto'] ?? ($product ?: $lineCode),
                'familia' => $row['familia'] ?? '',
                'subfamilia' => $row['subfamilia'] ?? '',
            ], embedded_matrix_rows($lineCode));
        }
    }

    $filtered = [];
    foreach ($rows as $row) {
        $essay = trim((string) ($row['ensayo'] ?? ''));
        if ($essay === '' || strtoupper($essay) === 'PENDIENTE MATRIZ DE ENSAYOS') {
            continue;
        }

        $filtered[normalize_essay_name($essay)] = $row;
    }

    return array_values($filtered);
}

function detect_product_line_from_text(string $text): ?string
{
    $text = mb_strtolower($text, 'UTF-8');
    if (str_contains($text, 'retie')) return 'RETIE';
    if (str_contains($text, 'retilap')) return 'RETILAP';
    if (str_contains($text, 'vajilla') || str_contains($text, 'ceram') || str_contains($text, 'vidrio')) return 'VAJILLAS';
    if (str_contains($text, 'juguete')) return 'JUGUETES';
    return null;
}

function best_lab_match(PDO $pdo, string $essay, string $norm = ''): ?array
{
    $essayKey = normalize_essay_name($essay);
    $normKey = normalize_essay_name($norm);
    $likeEssay = '%' . $essayKey . '%';
    $like = db_like_operator($pdo);

    $statement = $pdo->prepare(
        "SELECT le.*, l.nombre AS laboratorio_nombre, l.descuento_porcentaje
         FROM laboratorio_ensayos le
         INNER JOIN laboratorios l ON l.id = le.laboratorio_id
         WHERE l.activo = 1
           AND (
             le.ensayo_normalizado = :essay_exact
             OR le.ensayo_normalizado {$like} :like_essay
             OR :essay_text {$like} CONCAT('%', le.ensayo_normalizado, '%')
           )
         ORDER BY (le.costo_ensayo * (1 - LEAST(GREATEST(l.descuento_porcentaje, 0), 100) / 100)) DESC
         LIMIT 1"
    );
    $statement->execute([
        'essay_exact' => $essayKey,
        'like_essay' => $likeEssay,
        'essay_text' => $essayKey,
    ]);
    $match = $statement->fetch();

    if ($match || $normKey === '') {
        return $match ?: null;
    }

    $statement = $pdo->prepare(
        "SELECT le.*, l.nombre AS laboratorio_nombre, l.descuento_porcentaje
         FROM laboratorio_ensayos le
         INNER JOIN laboratorios l ON l.id = le.laboratorio_id
         WHERE l.activo = 1
           AND le.documento_normalizado {$like} :norm_key
         ORDER BY (le.costo_ensayo * (1 - LEAST(GREATEST(l.descuento_porcentaje, 0), 100) / 100)) DESC
         LIMIT 1"
    );
    $statement->execute(['norm_key' => '%' . $normKey . '%']);
    $match = $statement->fetch();

    return $match ?: null;
}

function prequote_rows(PDO $pdo, array $request, float $marginPercent, float $defaultQuantity = 1): array
{
    $rows = [];
    foreach (request_required_tests($pdo, $request) as $required) {
        $match = best_lab_match($pdo, (string) ($required['ensayo'] ?? ''), (string) ($required['norma_tecnica'] ?? ''));
        $cost = $match ? (float) $match['costo_ensayo'] : 0.0;
        $discount = $match ? (float) $match['descuento_porcentaje'] : 0.0;
        $discountedCost = $cost * (1 - max(min($discount, 100), 0) / 100);
        $value = margin_value($discountedCost, $marginPercent);

        $rows[] = [
            'laboratorio_ensayo_id' => $match['id'] ?? null,
            'laboratorio_id' => $match['laboratorio_id'] ?? null,
            'codigo' => $match['codigo_sm'] ?? '',
            'codigo_lab' => $match['codigo_ensayo_lab'] ?? '',
            'laboratorio' => $match['laboratorio_nombre'] ?? 'Sin laboratorio',
            'ensayo' => $required['ensayo'] ?? '',
            'producto' => $request['producto'] ?: ($required['producto'] ?? ''),
            'cantidad' => $defaultQuantity,
            'metodo_norma' => $match['documento_normativo'] ?? ($required['norma_tecnica'] ?? ''),
            'costo_unitario' => $discountedCost,
            'valor_unitario' => $value,
            'subtotal_costo' => $discountedCost * $defaultQuantity,
            'subtotal_valor' => $value * $defaultQuantity,
        ];
    }

    return $rows;
}
