<?php
require_once __DIR__.'/src/config.php';
require_once __DIR__.'/src/http.php';
require_once __DIR__.'/src/utils.php';
require_once __DIR__.'/src/gpsService.php';
require_once __DIR__.'/src/reportsService.php';

use Api\Http;
use Api\ReportsService;

$action = $_GET['action'] ?? null;

switch ($action) {
    case 'fetch_reports':
    $inicio = $_GET['inicio'] ?? date('Y-m-d');
    $fin    = $_GET['fin']    ?? date('Y-m-d');
    $filter = $_GET['filter'] ?? 'todas';
    $payload = ReportsService::fetch($inicio, $fin, $filter);
    Http::jsonResponse($payload);
    break;

    default:
    Http::jsonResponse(['ok'=>false,'error'=>'acción no soportada'], 400);
}
