<?php
// api/src/Http.php
namespace Api;

class Http {
    public static function jsonResponse($data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function getJson(string $url, array $headers = []): array {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($resp === false) return [null, $code ?: 500, $err ?: 'curl GET error'];
        $json = json_decode($resp, true);
        if ($json === null && json_last_error() !== JSON_ERROR_NONE)
            return [null, $code ?: 500, 'JSON decode error: '.json_last_error_msg()];
        return [$json, $code, null];
    }

    public static function postJson(string $url, $payload, array $headers = []): array {
        $ch = curl_init();
        $headers = array_merge($headers, ['Content-Type: application/json']);
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($resp === false) return [null, $code ?: 500, $err ?: 'curl POST error'];
        $json = json_decode($resp, true);
        if ($json === null && json_last_error() !== JSON_ERROR_NONE)
            return [null, $code ?: 500, 'JSON decode error: '.json_last_error_msg()];
        return [$json, $code, null];
    }
}
