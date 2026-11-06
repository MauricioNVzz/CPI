<?php
session_start();

header("Content-Type: application/json; charset=utf-8");

require_once "../conexion.php";
require_once "../middleware/auth.php";

// Solo maestros y administradores
verificarAcceso([1, 2]);

$usuarioId = $_SESSION["usuario_id"];
$rol = $_SESSION["usuario_rol"];

$desde = $_GET["desde"] ?? null;
$hasta = $_GET["hasta"] ?? null;
$vista = $_GET["vista"] ?? "detalle";

// Validar vista
if (!in_array($vista, ["detalle", "resumen"])) {
    http_response_code(400);
    echo json_encode([
        "error" => true,
        "mensaje" => "La vista solicitada no es válida."
    ]);
    exit;
}

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

    if ($vista === "detalle") {

        $sql = "
            SELECT
                v.Venta_id,
                a.Usuario_nombre AS Alumno,
                c.Curso_nombre,
                v.Venta_costo,
                v.Venta_fecha,
                v.Venta_estado,
                v.Venta_moneda
            FROM Ventas v
            INNER JOIN Cursos c
                ON v.Venta_id_curso = c.Curso_id
            INNER JOIN Usuarios a
                ON v.Venta_id_usuario = a.Usuario_id
            WHERE 1 = 1
        ";

    } else {

        $sql = "
            SELECT
                c.Curso_id,
                c.Curso_nombre,
                COUNT(v.Venta_id) AS Total_ventas,
                COALESCE(SUM(
                    CASE
                        WHEN v.Venta_estado = 'Completada'
                        THEN v.Venta_costo
                        ELSE 0
                    END
                ), 0) AS Ingresos,
                'MXN' AS Venta_moneda
            FROM Cursos c
            LEFT JOIN Ventas v
                ON c.Curso_id = v.Venta_id_curso
        ";

    }

    $tipos = "";
    $parametros = [];

    // Los maestros solo consultan sus propios cursos
    if ($rol == 2) {
        if ($vista === "detalle") {
            $sql .= " AND c.Curso_id_maestro = ?";
        } else {
            $sql .= " WHERE c.Curso_id_maestro = ?";
        }

        $tipos .= "i";
        $parametros[] = $usuarioId;
    }

    if ($desde) {
        $sql .= $vista === "detalle"
            ? " AND v.Venta_fecha >= ?"
            : " AND (v.Venta_fecha >= ? OR v.Venta_id IS NULL)";

        $tipos .= "s";
        $parametros[] = $desde;
    }

    if ($hasta) {
        $sql .= $vista === "detalle"
            ? " AND v.Venta_fecha < DATE_ADD(?, INTERVAL 1 DAY)"
            : " AND (v.Venta_fecha < DATE_ADD(?, INTERVAL 1 DAY) OR v.Venta_id IS NULL)";

        $tipos .= "s";
        $parametros[] = $hasta;
    }

    if ($vista === "detalle") {
        $sql .= " ORDER BY v.Venta_fecha DESC";
    } else {
        $sql .= "
            GROUP BY c.Curso_id, c.Curso_nombre
            ORDER BY Ingresos DESC
        ";
    }

    $stmt = $conexion->prepare($sql);

    if (!empty($parametros)) {
        $stmt->bind_param($tipos, ...$parametros);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();

    $datos = [];

    while ($fila = $resultado->fetch_assoc()) {
        $datos[] = $fila;
    }

    echo json_encode([
        "error" => false,
        "vista" => $vista,
        "moneda" => "MXN",
        "total" => count($datos),
        "datos" => $datos
    ], JSON_UNESCAPED_UNICODE);

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);

    echo json_encode([
        "error" => true,
        "mensaje" => "Error al generar el reporte de ventas."
    ]);
}
?>