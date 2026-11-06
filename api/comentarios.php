<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../conexion.php";
require_once __DIR__ . "/../controllers/ComentarioController.php";

function responder($codigo, $datos)
{
    http_response_code($codigo);
    echo json_encode(
        $datos,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

try {
    $metodo = $_SERVER["REQUEST_METHOD"];

    if (!in_array($metodo, ["GET", "POST"], true)) {
        header("Allow: GET, POST");
        responder(405, [
            "error" => "Método no permitido."
        ]);
    }

    // Obtener el ID de la lección.
    if ($metodo === "GET") {
        $multimediaId = filter_var(
            $_GET["multimedia_id"] ?? null,
            FILTER_VALIDATE_INT
        );
    } else {
        $datos = json_decode(
            file_get_contents("php://input"),
            true
        );

        // También admite formularios tradicionales.
        if (!is_array($datos)) {
            $datos = $_POST;
        }

        $multimediaId = filter_var(
            $datos["multimedia_id"] ?? null,
            FILTER_VALIDATE_INT
        );
    }

    if (!$multimediaId || $multimediaId <= 0) {
        responder(400, [
            "error" => "El ID de la lección no es válido."
        ]);
    }

    // Comprobar que la lección existe y obtener su curso.
    $sql = "
        SELECT
            Multimedia_id,
            Multimedia_id_curso,
            Multimedia_gratuito
        FROM Multimedia
        WHERE Multimedia_id = ?
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception("Error al consultar la lección.");
    }

    $stmt->bind_param("i", $multimediaId);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $leccion = $resultado->fetch_assoc();

    $stmt->close();

    if (!$leccion) {
        responder(404, [
            "error" => "La lección no existe."
        ]);
    }

    // Verificar si la lección es gratuita o si el usuario
    // compró el curso correspondiente.
    $puedeAcceder = (bool) $leccion["Multimedia_gratuito"];

    if (
        !$puedeAcceder &&
        isset($_SESSION["usuario_id"])
    ) {
        $usuarioId = (int) $_SESSION["usuario_id"];
        $cursoId = (int) $leccion["Multimedia_id_curso"];

        $sqlCompra = "
            SELECT Venta_id
            FROM Ventas
            WHERE Venta_id_usuario = ?
              AND Venta_id_curso = ?
              AND Venta_estado = 'Completada'
            LIMIT 1
        ";

        $stmtCompra = $conexion->prepare($sqlCompra);

        if (!$stmtCompra) {
            throw new Exception("Error al verificar la compra.");
        }

        $stmtCompra->bind_param(
            "ii",
            $usuarioId,
            $cursoId
        );

        $stmtCompra->execute();

        $resultadoCompra = $stmtCompra->get_result();
        $puedeAcceder = $resultadoCompra->num_rows > 0;

        $stmtCompra->close();
    }

    if (!$puedeAcceder) {
        responder(403, [
            "error" => "Necesitas adquirir el curso para acceder a esta lección."
        ]);
    }

    $comentarioController = new ComentarioController($conexion);

    // GET: consultar los comentarios de la lección.
    if ($metodo === "GET") {
        $comentarios = $comentarioController
            ->obtenerPorMultimedia($multimediaId);

        responder(200, [
            "comentarios" => $comentarios
        ]);
    }

    // POST: registrar un comentario.
    if (!isset($_SESSION["usuario_id"])) {
        responder(401, [
            "error" => "Debes iniciar sesión para comentar."
        ]);
    }

    $contenido = trim((string) ($datos["contenido"] ?? ""));

    if ($contenido === "") {
        responder(400, [
            "error" => "Escribe un comentario antes de enviarlo."
        ]);
    }

    if (mb_strlen($contenido) > 2000) {
        responder(400, [
            "error" => "El comentario no puede superar los 2000 caracteres."
        ]);
    }

    $usuarioId = (int) $_SESSION["usuario_id"];

    $comentarioId = $comentarioController->crear(
        $usuarioId,
        $multimediaId,
        $contenido
    );

    responder(201, [
        "mensaje" => "Comentario publicado correctamente.",
        "id" => $comentarioId
    ]);

} catch (InvalidArgumentException $e) {
    responder(400, [
        "error" => $e->getMessage()
    ]);
} catch (Throwable $e) {
    error_log("Error en api/comentarios.php: " . $e->getMessage());

    responder(500, [
        "error" => "Ocurrió un error al procesar los comentarios."
    ]);
}