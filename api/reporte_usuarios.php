<?php
session_start();

header("Content-Type: application/json; charset=utf-8");

require_once "../conexion.php";
require_once "../middleware/auth.php";

// Solo el administrador puede consultar este reporte
verificarAcceso([1]);

$desde = $_GET["desde"] ?? null;
$hasta = $_GET["hasta"] ?? null;
$rol = $_GET["rol"] ?? null;

// Validar fechas
if ($desde && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
    http_response_code(400);
    echo json_encode([
        "error" => true,
        "mensaje" => "La fecha inicial no es válida."
    ]);
    exit;
}

if ($hasta && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
    http_response_code(400);
    echo json_encode([
        "error" => true,
        "mensaje" => "La fecha final no es válida."
    ]);
    exit;
}

if ($desde && $hasta && $desde > $hasta) {
    http_response_code(400);
    echo json_encode([
        "error" => true,
        "mensaje" => "La fecha inicial no puede ser posterior a la fecha final."
    ]);
    exit;
}

try {

    $sql = "
        SELECT
            u.Usuario_id,
            u.Usuario_nombre,
            u.Usuario_email,
            r.Rol_nombre,
            u.Usuario_fechareg
        FROM Usuarios u
        INNER JOIN Roles r
            ON u.Usuario_id_rol = r.Rol_id
        WHERE 1 = 1
    ";

    $tipos = "";
    $parametros = [];

    if ($desde) {
        $sql .= " AND u.Usuario_fechareg >= ?";
        $tipos .= "s";
        $parametros[] = $desde;
    }

    if ($hasta) {
        $sql .= " AND u.Usuario_fechareg < DATE_ADD(?, INTERVAL 1 DAY)";
        $tipos .= "s";
        $parametros[] = $hasta;
    }

    if ($rol !== null && $rol !== "") {

        if (!ctype_digit((string)$rol)) {
            http_response_code(400);
            echo json_encode([
                "error" => true,
                "mensaje" => "El rol seleccionado no es válido."
            ]);
            exit;
        }

        $sql .= " AND u.Usuario_id_rol = ?";
        $tipos .= "i";
        $parametros[] = (int)$rol;
    }

    $sql .= " ORDER BY u.Usuario_fechareg DESC";

    $stmt = $conexion->prepare($sql);

    if (!empty($parametros)) {
        $stmt->bind_param($tipos, ...$parametros);
    }

    $stmt->execute();

    $resultado = $stmt->get_result();
    $usuarios = [];

    while ($fila = $resultado->fetch_assoc()) {
        $usuarios[] = $fila;
    }

    echo json_encode([
        "error" => false,
        "total" => count($usuarios),
        "usuarios" => $usuarios
    ], JSON_UNESCAPED_UNICODE);

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);

    echo json_encode([
        "error" => true,
        "mensaje" => "Error al generar el reporte de usuarios."
    ]);
}
?>