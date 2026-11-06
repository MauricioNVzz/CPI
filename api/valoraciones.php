
<?php

session_start();

require_once "../conexion.php";
require_once "../controllers/ValoracionController.php";
require_once "../controllers/VentaController.php";

header("Content-Type: application/json; charset=UTF-8");

$valoracionController = new ValoracionController($conexion);
$ventaController = new VentaController($conexion);

// Obtener método HTTP
$metodo = $_SERVER["REQUEST_METHOD"];

// Función para responder en JSON
function responder($error, $mensaje, $datos = null, $codigo = 200) {
    http_response_code($codigo);

    $respuesta = [
        "error" => $error,
        "mensaje" => $mensaje
    ];

    if ($datos !== null) {
        $respuesta["datos"] = $datos;
    }

    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}


// ==========================================
// GET: Consultar valoraciones de un curso
// ==========================================

if ($metodo === "GET") {

    if (!isset($_GET["curso_id"]) || !is_numeric($_GET["curso_id"])) {
        responder(true, "Debes proporcionar un curso válido.", null, 400);
    }

    $cursoId = (int)$_GET["curso_id"];

    if ($cursoId <= 0) {
        responder(true, "El ID del curso no es válido.", null, 400);
    }

    $resumen = $valoracionController->obtenerResumen($cursoId);

    if ($resumen === false) {
        responder(true, "Error al consultar las valoraciones.", null, 500);
    }

    $datos = [
        "promedio" => round((float)$resumen["promedio"], 1),
        "total" => (int)$resumen["total"],
        "mi_valoracion" => null
    ];

    // Si hay sesión iniciada, consultar su valoración
    if (isset($_SESSION["usuario_id"])) {

        $usuarioId = (int)$_SESSION["usuario_id"];

        $miValoracion = $valoracionController->obtenerPorUsuario(
            $usuarioId,
            $cursoId
        );

        if ($miValoracion !== false) {
            $datos["mi_valoracion"] = $miValoracion;
        }
    }

    responder(false, "Valoraciones obtenidas correctamente.", $datos);
}


// ==========================================
// POST: Guardar o actualizar valoración
// ==========================================

if ($metodo === "POST") {

    // Verificar sesión
    if (!isset($_SESSION["usuario_id"])) {
        responder(true, "Debes iniciar sesión para valorar.", null, 401);
    }

    // Solo alumnos pueden valorar
    if (!isset($_SESSION["usuario_rol"]) || (int)$_SESSION["usuario_rol"] !== 3) {
        responder(true, "Solo los alumnos pueden valorar cursos.", null, 403);
    }

    // Obtener datos enviados en JSON
    $datosEntrada = json_decode(file_get_contents("php://input"), true);

    if (!is_array($datosEntrada)) {
        responder(true, "Los datos enviados no son válidos.", null, 400);
    }

    if (
        !isset($datosEntrada["curso_id"]) ||
        !isset($datosEntrada["puntuacion"]) ||
        !is_numeric($datosEntrada["curso_id"]) ||
        !is_numeric($datosEntrada["puntuacion"])
    ) {
        responder(true, "Debes proporcionar el curso y la puntuación.", null, 400);
    }

    $cursoId = (int)$datosEntrada["curso_id"];
    $puntuacion = (int)$datosEntrada["puntuacion"];
    $usuarioId = (int)$_SESSION["usuario_id"];

    // Validar curso
    if ($cursoId <= 0) {
        responder(true, "El curso no es válido.", null, 400);
    }

    // Validar puntuación entre 1 y 5
    if ($puntuacion < 1 || $puntuacion > 5) {
        responder(true, "La puntuación debe estar entre 1 y 5.", null, 400);
    }

    // Verificar que el alumno haya comprado el curso
    if (!$ventaController->comprobarCompra($usuarioId, $cursoId)) {
    responder(true, "Debes comprar el curso antes de valorarlo.", null, 403);
}

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        responder(true, "Error al verificar la compra.", null, 500);
    }

    $stmt->bind_param("ii", $usuarioId, $cursoId);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $compra = $resultado->fetch_assoc();

    $stmt->close();

    if (!$compra) {
        responder(true, "Debes comprar el curso antes de valorarlo.", null, 403);
    }

    // Guardar o actualizar valoración
    $guardado = $valoracionController->guardar(
        $usuarioId,
        $cursoId,
        $puntuacion
    );

    if (!$guardado) {
        responder(true, "No se pudo guardar la valoración.", null, 500);
    }

    responder(false, "Valoración guardada correctamente.");
}


// ==========================================
// Métodos no permitidos
// ==========================================

responder(true, "Método no permitido.", null, 405);

?>
