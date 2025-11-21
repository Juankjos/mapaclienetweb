<?php
$DB_HOST = '167.99.163.209';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'clientes';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_error) {
    die('Error de conexión: ' . $mysqli->connect_error);
}

// Opcionales de seguridad/calidad
$mysqli->set_charset('utf8mb4');
