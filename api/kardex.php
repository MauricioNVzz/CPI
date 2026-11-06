<?php
session_start();

header("Content-Type: application/json; charset=utf-8");

require_once "../conexion.php";
require_once "../middleware/auth.php";

// Solo los alumnos pueden consultar su Kardex
verificarAcceso([3]);

$usuarioId = $_SESSION["usuario_id"];

// Filtros opcionales
$desde = $_GET["desde"] ?? null;
$hasta = $_GET["hasta"] ?? null;

// Validar formato de fechas
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
            v.Venta_id,
            c.Curso_nombre,
            m.Usuario_nombre AS Maestro,
            v.Venta_fecha,
            v.Venta_costo,
            v.Venta_estado,
            v.Venta_moneda
        FROM Ventas v
        INNER JOIN Cursos c
            ON v.Venta_id_curso = c.Curso_id
        INNER JOIN Usuarios m
            ON c.Curso_id_maestro = m.Usuario_id
        WHERE v.Venta_id_usuario = ?
    ";

    $tipos = "i";
    $parametros = [$usuarioId];

    if ($desde) {
        $sql .= " AND v.Venta_fecha >= ?";
        $tipos .= "s";
        $parametros[] = $desde;
    }

    if ($hasta) {
        $sql .= " AND v.Venta_fecha < DATE_ADD(?, INTERVAL 1 DAY)";
        $tipos .= "s";
        $parametros[] = $hasta;
    }

    $sql .= " ORDER BY v.Venta_fecha DESC";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param($tipos, ...$parametros);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $ventas = [];

    while ($fila = $resultado->fetch_assoc()) {
        $ventas[] = $fila;
    }

    echo json_encode([
        "error" => false,
        "total" => count($ventas),
        "moneda" => "MXN",
        "kardex" => $ventas
    ], JSON_UNESCAPED_UNICODE);

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);

    echo json_encode([
        "error" => true,
        "mensaje" => "Error al consultar el Kardex."
    ]);
}
?>