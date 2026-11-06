<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../conexion.php";
require_once __DIR__ . "/../controllers/ProgresoController.php";
require_once __DIR__ . "/../models/Certificado.php";

// Responder en formato JSON
function responder($codigo, $datos)
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

// Solo permitir peticiones POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(405, [
        "error" => true,
        "mensaje" => "Método no permitido."
    ]);
}

// Comprobar sesión
if (
    !isset($_SESSION["usuario_id"]) ||
    !isset($_SESSION["usuario_rol"]) ||
    (int)$_SESSION["usuario_rol"] !== 3
) {
    responder(401, [
        "error" => true,
        "mensaje" => "Debes iniciar sesión como alumno."
    ]);
}

// Obtener datos enviados
$datos = json_decode(file_get_contents("php://input"), true);

if (!is_array($datos)) {
    responder(400, [
        "error" => true,
        "mensaje" => "Los datos enviados no son válidos."
    ]);
}

$cursoId = filter_var(
    $datos["curso_id"] ?? null,
    FILTER_VALIDATE_INT
);

$multimediaId = filter_var(
    $datos["multimedia_id"] ?? null,
    FILTER_VALIDATE_INT
);

if (
    $cursoId === false ||
    $cursoId === null ||
    $cursoId <= 0 ||
    $multimediaId === false ||
    $multimediaId === null ||
    $multimediaId <= 0
) {
    responder(400, [
        "error" => true,
        "mensaje" => "El curso o contenido no es válido."
    ]);
}

$usuarioId = (int)$_SESSION["usuario_id"];

try {

    // Comprobar que el alumno compró el curso
    $sqlCompra = "
        SELECT Venta_id
        FROM Ventas
        WHERE Venta_id_usuario = ?
          AND Venta_id_curso = ?
          AND Venta_estado = 'Completada'
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sqlCompra);

    if (!$stmt) {
        throw new Exception("Error al comprobar la compra.");
    }

    $stmt->bind_param("ii", $usuarioId, $cursoId);
    $stmt->execute();

    $compra = $stmt->get_result();

    $comprado = $compra->num_rows > 0;

    $stmt->close();

    if (!$comprado) {
        responder(403, [
            "error" => true,
            "mensaje" => "Debes adquirir el curso para registrar tu avance."
        ]);
    }

    // Comprobar que el contenido pertenece al curso
    $sqlContenido = "
        SELECT m.Multimedia_id
        FROM multimedia m
        INNER JOIN niveles n
            ON m.Multimedia_id_nivel = n.Nivel_id
        WHERE m.Multimedia_id = ?
          AND n.Nivel_id_curso = ?
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sqlContenido);

    if (!$stmt) {
        throw new Exception("Error al comprobar el contenido.");
    }

    $stmt->bind_param("ii", $multimediaId, $cursoId);
    $stmt->execute();

    $contenido = $stmt->get_result();

    $existeContenido = $contenido->num_rows > 0;

    $stmt->close();

    if (!$existeContenido) {
        responder(404, [
            "error" => true,
            "mensaje" => "El contenido no pertenece a este curso."
        ]);
    }

    // Registrar el avance
    $progresoController = new ProgresoController($conexion);

    // Registrar el contenido como completado
$progresoController->completar(
    $usuarioId,
    $cursoId,
    $multimediaId
);

// Comprobar si todos los contenidos están completados
$cursoTerminado = $progresoController->cursoTerminado(
    $usuarioId,
    $cursoId
);

$certificadoFolio = null;

// Generar certificado si el curso terminó
if ($cursoTerminado) {

    $certificado = new Certificado($conexion);

    $certificadoFolio = $certificado->generar(
        $usuarioId,
        $cursoId
    );
}

responder(200, [
    "error" => false,
    "mensaje" => $cursoTerminado
        ? "¡Felicidades! Has terminado el curso y tu certificado está disponible."
        : "Contenido marcado como completado.",
    "curso_terminado" => $cursoTerminado,
    "certificado_folio" => $certificadoFolio
]);
} catch (Throwable $e) {

    error_log("Error en progreso.php: " . $e->getMessage());

    responder(500, [
        "error" => true,
        "mensaje" => "Ocurrió un error al registrar el avance."
    ]);
}
