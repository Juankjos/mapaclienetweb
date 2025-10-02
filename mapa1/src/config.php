<?php
namespace Api;

final class Config {
    public static function db(): array {
        return [
            'host' => getenv('DB_HOST') ?: '',
            'name' => getenv('DB_NAME') ?: '',
            'user' => getenv('DB_USER') ?: '',
            'pass' => getenv('DB_PASS') ?: '',
        ];
    }

    public static function internalBase(): string {
        return getenv('INTERNAL_BASE') ?: 'http://127.0.0.1:9091';
    }

    public static function token(): string {
        return getenv('INTERNAL_TOKEN') ?: '___token_super_secreto___';
    }

    public static function epReportes(): string {
        return self::internalBase() . '/reportes_x_tipo.php';
    }
    public static function epGpsBulk(): string {
        return self::internalBase() . '/gps/bulk';
    }
}
