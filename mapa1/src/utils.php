<?php
// api/src/Utils.php
namespace Api;

class Utils {
    public static function normalizeContract($c): string {
        $c = trim((string)$c);
        if ($c === '') return '';
        if (preg_match('/^0*(\d+)(?:-\d+)?$/', $c, $m)) {
            $v = ltrim($m[1], '0');
            return $v === '' ? '0' : $v;
        }
        $digits = preg_replace('/\D+/', '', $c);
        if ($digits === '') return '';
        $v = ltrim($digits, '0');
        return $v === '' ? '0' : $v;
    }

    public static function computeStatus(array $r): string {
        $hasSol = !empty($r['solucion']);
        $hasExe = !empty($r['fecha_ejecucion']);
        return ($hasSol && $hasExe) ? 'ejecutadas' : 'pendientes';
    }
}
