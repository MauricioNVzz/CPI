
<?php

header("Content-Type: application/json; charset=UTF-8");

session_start();

require_once __DIR__ . "/../conexion.php";
require_once __DIR__ . "/../controllers/MensajeController.php";

function responder($codigo, $datos)
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

try {

    // Verificar sesión
    if (
        !isset($_SESSION["usuario_id"]) ||
        !isset($_SESSION["usuario_rol"])
    ) {
        responder(401, [
            "error" => true,
            "mensaje" => "Debes iniciar sesión."
        ]);
    }

    $usuarioId = (int) $_SESSION["usuario_id"];
    $rol = (int) $_SESSION["usuario_rol"];

    // Solo administradores y maestros pueden utilizar el chat
    if ($rol !== 1 && $rol !== 2) {
        responder(403, [
            "error" => true,
            "mensaje" => "No tienes permiso para utilizar el chat."
        ]);
    }

    $mensajeController = new MensajeController($conexion);

    $metodo = $_SERVER["REQUEST_METHOD"];

    /*
    |--------------------------------------------------------------------------
    | Obtener el identificador del administrador
    |--------------------------------------------------------------------------
    */

    function obtenerAdministrador($conexion)
    {
        $sql = "
            SELECT Usuario_id
            FROM Usuarios
            WHERE Usuario_id_rol = 1
            ORDER BY Usuario_id ASC
            LIMIT 1
        ";

        $resultado = $conexion->query($sql);

        if (!$resultado) {
            throw new Exception("Error al buscar al administrador.");
        }

        $administrador = $resultado->fetch_assoc();

        if (!$administrador) {
            return null;
        }

        return (int) $administrador["Usuario_id"];
    }

    /*
    |--------------------------------------------------------------------------
    | Determinar el otro participante de la conversación
    |--------------------------------------------------------------------------
    */

    function obtenerParticipante($conexion, $usuarioId, $rol)
    {
        if ($rol === 2) {

            // Si es maestro, su destinatario será el administrador
            return obtenerAdministrador($conexion);

        } else {

            // Si es administrador, debe indicar el maestro
            $maestroId = filter_var(
                $_GET["usuario_id"] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$maestroId || $maestroId <= 0) {
                responder(400, [
                    "error" => true,
                    "mensaje" => "Debes indicar el maestro de la conversación."
                ]);
            }

            if ($maestroId === $usuarioId) {
                responder(400, [
                    "error" => true,
                    "mensaje" => "No puedes abrir una conversación contigo mismo."
                ]);
            }

            $sql = "
                SELECT Usuario_id
                FROM Usuarios
                WHERE Usuario_id = ?
                AND Usuario_id_rol = 2
            ";

            $stmt = $conexion->prepare($sql);

            if (!$stmt) {
                throw new Exception("Error al validar al maestro.");
            }

            $stmt->bind_param("i", $maestroId);
            $stmt->execute();

            $resultado = $stmt->get_result();
            $maestro = $resultado->fetch_assoc();

            $stmt->close();

            if (!$maestro) {
                responder(404, [
                    "error" => true,
                    "mensaje" => "El maestro seleccionado no existe."
                ]);
            }

            return (int) $maestro["Usuario_id"];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET: Consultar conversación
    |--------------------------------------------------------------------------
    */

    if ($metodo === "GET") {

    
// Obtener lista de maestros (solo administrador)
if (isset($_GET["maestros"]) && $_GET["maestros"] == "1") {

    if ($rol !== 1) {
        responder(403, [
            "error" => true,
            "mensaje" => "No tienes permiso para consultar esta lista."
        ]);
    }

    $sql = "
        SELECT
            Usuario_id,
            Usuario_nombre,
            Usuario_email,
            Usuario_foto
        FROM Usuarios
        WHERE Usuario_id_rol = 2
        ORDER BY Usuario_nombre ASC
    ";

    $resultado = $conexion->query($sql);

    if (!$resultado) {
        throw new Exception("Error al consultar los maestros.");
    }

    $maestros = [];

    while ($fila = $resultado->fetch_assoc()) {
        $maestros[] = [
            "id" => (int) $fila["Usuario_id"],
            "nombre" => $fila["Usuario_nombre"],
            "email" => $fila["Usuario_email"],
            "foto" => $fila["Usuario_foto"]
        ];
    }

    responder(200, [
        "error" => false,
        "total" => count($maestros),
        "maestros" => $maestros
    ]);
}


        $otroUsuario = obtenerParticipante(
            $conexion,
            $usuarioId,
            $rol
        );

        if (!$otroUsuario) {
            responder(404, [
                "error" => true,
                "mensaje" => "No se encontró un administrador registrado."
            ]);
        }

        $mensajes = $mensajeController->obtenerConversacion(
            $usuarioId,
            $otroUsuario
        );

        // Marcar como leídos los mensajes recibidos
        $mensajeController->marcarLeidos(
            $otroUsuario,
            $usuarioId
        );

        responder(200, [
            "error" => false,
            "total" => count($mensajes),
            "mensajes" => $mensajes
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST: Enviar mensaje
    |--------------------------------------------------------------------------
    */

    if ($metodo === "POST") {

        $datos = json_decode(file_get_contents("php://input"), true);

        if (!is_array($datos)) {
            responder(400, [
                "error" => true,
                "mensaje" => "Los datos enviados no son válidos."
            ]);
        }

        $contenido = trim($datos["contenido"] ?? "");

        if ($contenido === "") {
            responder(400, [
                "error" => true,
                "mensaje" => "El mensaje no puede estar vacío."
            ]);
        }

        if (mb_strlen($contenido) > 5000) {
            responder(400, [
                "error" => true,
                "mensaje" => "El mensaje no puede superar los 5000 caracteres."
            ]);
        }

        // El administrador debe indicar a qué maestro escribe.
        // El maestro siempre escribe al administrador.
        if ($rol === 2) {

            $receptor = obtenerAdministrador($conexion);

        } else {

            $receptor = filter_var(
                $datos["usuario_id"] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$receptor || $receptor <= 0) {
                responder(400, [
                    "error" => true,
                    "mensaje" => "Debes indicar el maestro destinatario."
                ]);
            }

            if ($receptor === $usuarioId) {
                responder(400, [
                    "error" => true,
                    "mensaje" => "No puedes enviarte mensajes a ti mismo."
                ]);
            }

            $sql = "
                SELECT Usuario_id
                FROM Usuarios
                WHERE Usuario_id = ?
                AND Usuario_id_rol = 2
            ";

            $stmt = $conexion->prepare($sql);

            if (!$stmt) {
                throw new Exception("Error al validar al destinatario.");
            }

            $stmt->bind_param("i", $receptor);
            $stmt->execute();

            $resultado = $stmt->get_result();
            $maestro = $resultado->fetch_assoc();

            $stmt->close();

            if (!$maestro) {
                responder(404, [
                    "error" => true,
                    "mensaje" => "El maestro destinatario no existe."
                ]);
            }
        }

        if (!$receptor) {
            responder(404, [
                "error" => true,
                "mensaje" => "No se encontró un administrador registrado."
            ]);
        }

        $idMensaje = $mensajeController->enviar(
            $usuarioId,
            $receptor,
            $contenido
        );

        responder(201, [
            "error" => false,
            "mensaje" => "Mensaje enviado correctamente.",
            "id" => $idMensaje
        ]);
    }

    // Método no permitido
    responder(405, [
        "error" => true,
        "mensaje" => "Método HTTP no permitido."
    ]);

} catch (Throwable $e) {

    error_log("Error en API de mensajes: " . $e->getMessage());

    responder(500, [
        "error" => true,
        "mensaje" => "Ocurrió un error al procesar los mensajes."
    ]);
}
