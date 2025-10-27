<?php
// auth.php
function can_view_map(mysqli $mysqli, string $contrato, int $reporteId = 0) {
    // Condición de acceso:
    // - En camino
    // - o Completado y sin calificación (Rate 0 o NULL)
    $whereStatus = " (p.Status = 'En camino' OR (p.Status = 'Completado' AND COALESCE(p.Rate,0) = 0)) ";

    if ($reporteId > 0) {
        $sql = "
            SELECT r.IDReporte, r.IDContrato, r.Problema, r.FechaAgendado,
                    p.IDTec, p.Status, p.Rate,
                    t.NombreTec, t.NumTec,
                    u.Direccion, u.Nombre AS NombreCliente
            FROM reportes r
            INNER JOIN usuarios   u ON u.IDContrato = r.IDContrato
            LEFT  JOIN produccion p ON p.IDReporte  = r.IDReporte AND p.IDContrato = r.IDContrato
            LEFT  JOIN tecnicos   t ON t.IdTec      = p.IDTec
            WHERE r.IDReporte = ? AND r.IDContrato = ? AND {$whereStatus}
            LIMIT 1
        ";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('is', $reporteId, $contrato);
    } else {
        // Prioriza En camino; si no hay, toma la más reciente Completado sin calificación
        $sql = "
            SELECT r.IDReporte, r.IDContrato, r.Problema, r.FechaAgendado,
                    p.IDTec, p.Status, p.Rate,
                    t.NombreTec, t.NumTec,
                    u.Direccion, u.Nombre AS NombreCliente
            FROM reportes r
            INNER JOIN usuarios   u ON u.IDContrato = r.IDContrato
            LEFT  JOIN produccion p ON p.IDReporte  = r.IDReporte AND p.IDContrato = r.IDContrato
            LEFT  JOIN tecnicos   t ON t.IdTec      = p.IDTec
            WHERE r.IDContrato = ? AND {$whereStatus}
            ORDER BY (p.Status='En camino') DESC, COALESCE(r.FechaAgendado,'1000-01-01') DESC, r.IDReporte DESC
            LIMIT 1
        ";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $contrato);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc() ?: null;
    $stmt->close();

    return $row ?: false;
}

function is_mesa(): bool {
    return isset($_SESSION['contrato']) && $_SESSION['contrato'] === 'Mesa';
}

function gate_mesa_only_these(): void {
    if (!is_mesa()) return;

    $allowed = [
        'rate_tec.php',
        'comentarios_tec.php',
        'registro_tecnico.php',
        'administrar_cuenta.php',
        'logout.php',
        'login.php', 
    ];
    $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if (!in_array($current, $allowed, true)) {
        header('Location: rate_tec.php');
        exit;
    }
}

function gate_only_mesa_for_these(): void {
    $restricted = [
        'rate_tec.php',
        'comentarios_tec.php',
        'registro_tecnico.php',
    ];
    $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if (in_array($current, $restricted, true) && !is_mesa()) {
        // Redirige al “home” de clientes
        header('Location: ordenes_servicio.php');
        exit;
    }
}