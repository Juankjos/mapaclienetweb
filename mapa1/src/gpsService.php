<?php
// api/src/GpsService.php
namespace Api;

class GpsService {
    public static function enrich(array $reports): array {
        // recolectar contratos
        $contracts = [];
        foreach ($reports as $r) {
            if (!isset($r['contrato'])) continue;
            $val = (string)$r['contrato'];
            if ($val !== '') $contracts[] = $val;
        }
        $contracts = array_values(array_unique($contracts));
        if (empty($contracts)) {
            return [$reports, ['count'=>0,'found'=>0,'not_found'=>[],'results'=>[]]];
        }

        [$gps, $code, $err] = Http::postJson(
            Config::epGpsBulk(),
            ['contracts' => $contracts],
            ['X-Internal-Auth: '.Config::token()]
        );

        if ($gps === null) {
            return [$reports, [
                'error'=>$err, 'http'=>$code, 'count'=>0, 'found'=>0,
                'not_found'=>$contracts, 'results'=>[]
            ]];
        }

        $byInput = $byNorm = [];
        foreach ($gps['results'] ?? [] as $g) {
            if (isset($g['contract_input']))      $byInput[(string)$g['contract_input']] = $g;
            if (isset($g['contract_normalized'])) $byNorm[(string)$g['contract_normalized']] = $g;
        }

        foreach ($reports as &$r) {
            $lat = $lon = null;
            $raw  = $r['contrato'] ?? '';
            $norm = Utils::normalizeContract($raw);

            if ($raw !== '' && isset($byInput[$raw]) && !isset($byInput[$raw]['error_code'])) {
                $lat = $byInput[$raw]['latitude'] ?? null;
                $lon = $byInput[$raw]['longitude'] ?? null;
            }
            if (($lat === null || $lon === null) && $norm !== '' &&
                isset($byNorm[$norm]) && !isset($byNorm[$norm]['error_code'])) {
                $lat = $byNorm[$norm]['latitude'] ?? null;
                $lon = $byNorm[$norm]['longitude'] ?? null;
            }
            if ($lat !== null && $lon !== null) {
                $r['latitude']  = $lat;
                $r['longitude'] = $lon;
            }
        }
        unset($r);

        return [$reports, $gps];
    }
}
