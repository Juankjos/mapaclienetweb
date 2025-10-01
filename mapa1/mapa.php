<?php
// ======================
// Configuración heredada
// ======================
$DB_HOST = '';
$DB_NAME = '';
$DB_USER = '';
$DB_PASS = '';

// ==========================
// Configuración NUEVO origen
// ==========================
$INTERNAL_BASE = 'http://127.0.0.1:9091';
$INTERNAL_TOKEN = '___token_super_secreto___';

// Endpoints específicos
$EP_REPORTES = $INTERNAL_BASE . '/reportes_x_tipo.php';
$EP_GPS_BULK = $INTERNAL_BASE . '/gps/bulk';

// ===============
// Utilidades PHP
// ===============
function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function curl_get_json($url, $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($resp === false) {
        return [null, $code ?: 500, $err ?: 'curl_get_json error'];
    }
    $json = json_decode($resp, true);
    if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
        return [null, $code ?: 500, 'JSON decode error: ' . json_last_error_msg()];
    }
    return [$json, $code, null];
}

function curl_post_json($url, $payload, $headers = []) {
    $ch = curl_init();
    $headers = array_merge($headers, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($resp === false) {
        return [null, $code ?: 500, $err ?: 'curl_post_json error'];
    }
    $json = json_decode($resp, true);
    if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
        return [null, $code ?: 500, 'JSON decode error: ' . json_last_error_msg()];
    }
    return [$json, $code, null];
}

// Normaliza contrato tipo "00012345-6" → "12345"
function normalize_contract($c) {
    $c = trim((string)$c);
    if ($c === '') return '';
    // Mantener sólo dígitos y guión para detectar sufijo; luego quitar ceros a la izquierda
    // Formato esperado: digitos[-digitos]
    if (preg_match('/^0*(\d+)(?:-\d+)?$/', $c, $m)) {
        return ltrim($m[1], '0') === '' ? '0' : ltrim($m[1], '0');
    }
    // fallback: quitar todo salvo dígitos, luego quitar ceros a la izquierda
    $digits = preg_replace('/\D+/', '', $c);
    return $digits === '' ? '' : (ltrim($digits, '0') === '' ? '0' : ltrim($digits, '0'));
}

// Enriquecer reportes con GPS usando /gps/bulk
function enrich_with_gps($reports, $gps_bulk_endpoint, $auth_header) {
    // Recopilar contratos tal como vienen y también normalizados
    $contracts = [];
    foreach ($reports as $r) {
        if (!isset($r['contrato'])) continue;
        $contracts[] = $r['contrato'];
    }
    $contracts = array_values(array_unique(array_filter($contracts, fn($x) => (string)$x !== '')));
    if (empty($contracts)) {
        return [$reports, ['count'=>0, 'found'=>0, 'not_found'=>[], 'results'=>[]]];
    }

    [$gps, $code, $err] = curl_post_json($gps_bulk_endpoint, ['contracts' => $contracts], [$auth_header]);
    if ($gps === null) {
        // Devolver sin GPS pero indicando error
        return [$reports, ['error' => $err, 'http' => $code, 'count'=>0, 'found'=>0, 'not_found'=>$contracts, 'results'=>[]]];
    }

    // Índices por contract_input y por contract_normalized
    $by_input = [];
    $by_norm  = [];
    foreach ($gps['results'] ?? [] as $g) {
        if (isset($g['contract_input'])) $by_input[(string)$g['contract_input']] = $g;
        if (isset($g['contract_normalized'])) $by_norm[(string)$g['contract_normalized']] = $g;
    }

    foreach ($reports as &$r) {
        $lat = null; $lon = null;
        $raw = $r['contrato'] ?? '';
        $norm = normalize_contract($raw);
        if ($raw !== '' && isset($by_input[$raw]) && !isset($by_input[$raw]['error_code'])) {
            $lat = $by_input[$raw]['latitude'] ?? null;
            $lon = $by_input[$raw]['longitude'] ?? null;
        }
        if (($lat === null || $lon === null) && $norm !== '' && isset($by_norm[$norm]) && !isset($by_norm[$norm]['error_code'])) {
            $lat = $by_norm[$norm]['latitude'] ?? null;
            $lon = $by_norm[$norm]['longitude'] ?? null;
        }
        if ($lat !== null && $lon !== null) {
            $r['latitude']  = $lat;
            $r['longitude'] = $lon;
        }
    }
    unset($r);

    return [$reports, $gps];
}

// Determinar estado de la falla
function compute_status($r) {
    $hasSol = isset($r['solucion']) && $r['solucion'] !== null && $r['solucion'] !== '';
    $hasExe = isset($r['fecha_ejecucion']) && $r['fecha_ejecucion'] !== null && $r['fecha_ejecucion'] !== '';
    return ($hasSol && $hasExe) ? 'ejecutadas' : 'pendientes';
}

// =====================
// API interna (misma URL)
// =====================
$action = $_GET['action'] ?? null;
if ($action === 'fetch_reports') {
    // Params UI: YYYY-MM-DD → Backend requiere YYYY/MM/DD
    $inicio_ui = $_GET['inicio'] ?? date('Y-m-d');
    $fin_ui    = $_GET['fin'] ?? date('Y-m-d');
    $filter    = $_GET['filter'] ?? 'todas'; // 'todas' | 'pendientes' | 'ejecutadas'

    // Validación básica de fechas
    $inicio_ui = preg_replace('/[^0-9-]/', '', $inicio_ui);
    $fin_ui    = preg_replace('/[^0-9-]/', '', $fin_ui);
    $inicio_q  = str_replace('-', '/', $inicio_ui);
    $fin_q     = str_replace('-', '/', $fin_ui);

    // 1) Obtener reportes por tipo=todas
    $url = $GLOBALS['EP_REPORTES'] . '?' . http_build_query([
        'inicio' => $inicio_q,
        'fin'    => $fin_q,
        'tipo'   => 'todas'
    ]);
    [$reports, $code1, $err1] = curl_get_json($url, ['X-Internal-Auth: ' . $GLOBALS['INTERNAL_TOKEN']]);
    if ($reports === null || !is_array($reports)) {
        json_response([
            'ok' => false,
            'error' => 'No se pudo obtener reportes_x_tipo',
            'http' => $code1,
            'detail' => $err1,
        ], 502);
    }

    // 2) Enriquecer con GPS en bulk
    [$enriched, $gps_meta] = enrich_with_gps($reports, $GLOBALS['EP_GPS_BULK'], 'X-Internal-Auth: ' . $GLOBALS['INTERNAL_TOKEN']);

    // 3) Calcular estado y filtrar (cliente pidió que SIEMPRE pidamos tipo=todas y filtremos nosotros)
    $out = [];
    foreach ($enriched as $r) {
        $status = compute_status($r); // 'pendientes' | 'ejecutadas'
        $r['estado'] = $status;
        if ($filter === 'todas' || $filter === $status) {
            // Sólo incluir con GPS válido
            if (isset($r['latitude']) && isset($r['longitude'])) {
                $out[] = $r;
            }
        }
    }

    json_response([
        'ok' => true,
        'params' => [
            'inicio' => $inicio_ui,
            'fin'    => $fin_ui,
            'filter' => $filter,
        ],
        'count' => count($out),
        'gps'   => $gps_meta,
        'data'  => $out,
    ]);
}
else if ($action === 'poll') {
    // No implementado en el nuevo flujo; devolver 501 para ser explícitos
    json_response(['ok'=>false,'error'=>'poll no disponible en v10.0'], 501);
}

?>