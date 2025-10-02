<?php
// api/src/ReportsService.php
namespace Api;

class ReportsService {
    public static function fetch(string $inicioUi, string $finUi, string $filter): array {
        // sanitizar fechas (YYYY-MM-DD → YYYY/MM/DD)
        $inicioUi = preg_replace('/[^0-9-]/', '', $inicioUi);
        $finUi    = preg_replace('/[^0-9-]/', '', $finUi);
        $inicioQ  = str_replace('-', '/', $inicioUi);
        $finQ     = str_replace('-', '/', $finUi);

        $url = Config::epReportes() . '?' . http_build_query([
            'inicio' => $inicioQ,
            'fin'    => $finQ,
            'tipo'   => 'todas'
        ]);
        [$reports, $code1, $err1] = Http::getJson($url, ['X-Internal-Auth: '.Config::token()]);
        if ($reports === null || !is_array($reports)) {
            Http::jsonResponse([
                'ok'=>false,'error'=>'No se pudo obtener reportes_x_tipo',
                'http'=>$code1,'detail'=>$err1
            ], 502);
        }

        [$enriched, $gpsMeta] = GpsService::enrich($reports);

        $out = [];
        foreach ($enriched as $r) {
            $status = Utils::computeStatus($r);
            $r['estado'] = $status;

            if ($filter === 'todas' || $filter === $status) {
                if (isset($r['latitude'], $r['longitude'])) $out[] = $r;
            }
        }

        return [
            'ok'=>true,
            'params'=>['inicio'=>$inicioUi, 'fin'=>$finUi, 'filter'=>$filter],
            'count'=>count($out),
            'gps'=>$gpsMeta,
            'data'=>$out
        ];
    }
}
