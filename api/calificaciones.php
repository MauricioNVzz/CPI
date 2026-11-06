
<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../conexion.php";
require_once __DIR__ . "/../controllers/CalificacionController.php";

// Responder en formato JSON
function responder($codigo, $datos)
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

// Comprobar sesión de alumno
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

$usuarioId = (int)$_SESSION["usuario_id"];

$metodo = $_SERVER["REQUEST_METHOD"];

try {

    $calificacionController = new CalificacionController($conexion);

    // GET: consultar promedio y calificación del alumno.
    if ($metodo === "GET") {

        $cursoId = filter_var(
            $_GET["curso_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            $cursoId === false ||
            $cursoId === null ||
            $cursoId <= 0
        ) {
            responder(400, [
                "error" => true,
                "mensaje" => "El curso no es válido."
            ]);
        }

        // Comprobar compra completada.
        $sqlCompra = "
            SELECT Venta_id
            FROM Ventas
            WHERE Venta_id_usuario = ?
              AND Venta_id_curso = ?
              AND Venta_estado = 'Completada'
            LIMIT 1
        ";

        $stmt = $conexion->prepare($sqlCompra);
        $stmt->bind_param("ii", $usuarioId, $cursoId);
        $stmt->execute();

        $comprado = $stmt->get_result()->num_rows > 0;

        $stmt->close();

        if (!$comprado) {
            responder(403, [
                "error" => true,
                "mensaje" => "Debes adquirir el curso para consultar sus calificaciones."
            ]);
        }

        $promedio = $calificacionController
            ->obtenerPromedio($cursoId);

        $miCalificacion = $calificacionController
            ->obtenerPorUsuarioCurso($usuarioId, $cursoId);

        responder(200, [
            "error" => false,
            "promedio" => (float)$promedio["promedio"],
            "total_calificaciones" => (int)$promedio["total_calificaciones"],
            "mi_calificacion" => $miCalificacion
                ? (int)$miCalificacion["Calificacion_valor"]
                : null
        ]);
    }

    // POST: registrar o actualizar calificación.
    if ($metodo === "POST") {

        $datos = json_decode(
            file_get_contents("php://input"),
            true
        );

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

        $valor = filter_var(
            $datos["calificacion"] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            $cursoId === false ||
            $cursoId === null ||
            $cursoId <= 0
        ) {
            responder(400, [
                "error" => true,
                "mensaje" => "El curso no es válido."
            ]);
        }

        if (
            $valor === false ||
            $valor === null ||
            $valor < 1 ||
            $valor > 10
        ) {
            responder(400, [
                "error" => true,
                "mensaje" => "La calificación debe ser un número entero entre 1 y 10."
            ]);
        }

        // Comprobar que el alumno compró el curso.
        $sqlCompra = "
            SELECT Venta_id
            FROM Ventas
            WHERE Venta_id_usuario = ?
              AND Venta_id_curso = ?
              AND Venta_estado = 'Completada'
            LIMIT 1
        ";

        $stmt = $conexion->prepare($sqlCompra);
        $stmt->bind_param("ii", $usuarioId, $cursoId);
        $stmt->execute();

        $comprado = $stmt->get_result()->num_rows > 0;

        $stmt->close();

        if (!$comprado) {
            responder(403, [
                "error" => true,
                "mensaje" => "Debes adquirir el curso para calificarlo."
            ]);
        }

        $calificacionController->guardar(
            $usuarioId,
            $cursoId,
            $valor
        );

        $promedio = $calificacionController
            ->obtenerPromedio($cursoId);

        responder(200, [
            "error" => false,
            "mensaje" => "Tu calificación se guardó correctamente.",
            "promedio" => (float)$promedio["promedio"],
            "total_calificaciones" => (int)$promedio["total_calificaciones"],
            "mi_calificacion" => $valor
        ]);
    }

    responder(405, [
        "error" => true,
        "mensaje" => "Método no permitido."
    ]);

} catch (Throwable $e) {

    error_log(
        "Error en calificaciones.php: " . $e->getMessage()
    );

    responder(500, [
        "error" => true,
        "mensaje" => "Ocurrió un error al procesar la calificación."
    ]);
}
