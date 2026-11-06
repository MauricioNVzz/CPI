<?php

// Orígenes autorizados para consumir la API.
$origenesPermitidos = [
    "http://localhost",
    "http://127.0.0.1"
];

$origen = $_SERVER["HTTP_ORIGIN"] ?? "";
$metodo = $_SERVER["REQUEST_METHOD"];

// Permitir únicamente los orígenes autorizados.
if (in_array($origen, $origenesPermitidos, true)) {
    header("Access-Control-Allow-Origin: " . $origen);
    header("Vary: Origin");
}

// Métodos y encabezados permitidos.
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token");

// Las respuestas de esta API utilizan JSON.
header("Content-Type: application/json; charset=utf-8");

// Resolver solicitudes preliminares de CORS.
if ($metodo === "OPTIONS") {
    http_response_code(204);
    exit;
}

// ==========================================
// INICIAR SESIÓN
// ==========================================

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ==========================================
// FUNCIÓN PARA RESPONDER EN JSON
// ==========================================

function responder($codigo, $datos)
{
    http_response_code($codigo);

    echo json_encode(
        $datos,
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
    );

    exit;
}

// ==========================================
// VALIDAR TOKEN CSRF
// ==========================================

if (in_array($metodo, ["POST", "PUT", "DELETE"], true)) {

    $tokenRecibido = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";

    if (
        empty($_SESSION["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $tokenRecibido)
    ) {
        responder(403, [
            "error" => true,
            "mensaje" => "Token CSRF inválido o ausente."
        ]);
    }
}

// ==========================================
// CONEXIÓN A MYSQL
// ==========================================

require_once __DIR__ . "/../conexion.php";

// ==========================================
// CARGAR CONTROLADOR DE CURSOS
// ==========================================

require_once __DIR__ . "/../controllers/CursoController.php";

$cursoController = new CursoController($conexion);

// GET

if ($metodo === "GET") {

    // Obtener todas las categorías
    if (isset($_GET["categorias"])) {

        try {

            $categorias = $cursoController->obtenerCategorias();

            responder(200, [
                "error" => false,
                "total" => count($categorias),
                "categorias" => $categorias
            ]);

        } catch (Throwable $e) {

            error_log("Error al obtener categorías: " . $e->getMessage());

            responder(500, [
                "error" => true,
                "mensaje" => "No fue posible obtener las categorías."
            ]);
        }
    }

    // Obtener un curso específico por ID
    if (isset($_GET["id"])) {

        $id = filter_input(
            INPUT_GET,
            "id",
            FILTER_VALIDATE_INT
        );

        if ($id === false || $id === null || $id <= 0) {
            responder(400, [
                "error" => true,
                "mensaje" => "El ID debe ser un número entero positivo."
            ]);
        }

        try {

            $curso = $cursoController->obtenerPorId($id);

            if ($curso === null) {
                responder(404, [
                    "error" => true,
                    "mensaje" => "Curso no encontrado."
                ]);
            }

            responder(200, [
                "error" => false,
                "curso" => $curso
            ]);

        } catch (Throwable $e) {

            responder(500, [
                "error" => true,
                "mensaje" => "No fue posible obtener el curso."
            ]);
        }
    }
    
    // Obtener cursos del maestro autenticado
    if (isset($_GET["instructor"])) {

        // Comprobar que exista una sesión
        if (
            !isset($_SESSION["usuario_id"]) ||
            !isset($_SESSION["usuario_rol"])
        ) {
            responder(401, [
                "error" => true,
                "mensaje" => "Debes iniciar sesión."
            ]);
        }

        // Comprobar que sea un maestro
        if ((int)$_SESSION["usuario_rol"] !== 2) {
            responder(403, [
                "error" => true,
                "mensaje" => "Solo los maestros pueden consultar esta información."
            ]);
        }

        $instructor = filter_input(
            INPUT_GET,
            "instructor",
            FILTER_VALIDATE_INT
        );

        // El ID solicitado debe corresponder al usuario autenticado
        if (
            $instructor === false ||
            $instructor === null ||
            $instructor <= 0 ||
            $instructor !== (int)$_SESSION["usuario_id"]
        ) {
            responder(403, [
                "error" => true,
                "mensaje" => "No puedes consultar los cursos de otro maestro."
            ]);
        }

        try {

            $cursos = $cursoController->obtenerPorMaestro($instructor);

            responder(200, [
                "error" => false,
                "total" => count($cursos),
                "cursos" => $cursos
            ]);

        } catch (Throwable $e) {

            responder(500, [
                "error" => true,
                "mensaje" => "No fue posible obtener los cursos del instructor."
            ]);
        }
    }


    // Obtener todos los cursos
    try {

        $cursos = $cursoController->obtenerTodos();

        responder(200, [
            "error" => false,
            "total" => count($cursos),
            "cursos" => $cursos
        ]);

    } catch (Throwable $e) {

        responder(500, [
            "error" => true,
            "mensaje" => "No fue posible obtener los cursos."
        ]);
    }
}

// POST: CREAR CURSO
if ($metodo === "POST" && !isset($_POST["accion"])) {

    // Comprobar que exista una sesión.
    if (!isset($_SESSION["usuario_id"])) {
        responder(401, [
            "error" => true,
            "mensaje" => "Debes iniciar sesión."
        ]);
    }

    // Esta operación está destinada a los maestros.
    if ((int) $_SESSION["usuario_rol"] !== 2) {
        responder(403, [
            "error" => true,
            "mensaje" => "No tienes permisos para crear cursos."
        ]);
    }

    // Obtener los datos del formulario.
    $titulo = trim($_POST["titulo"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $categoria = filter_var(
        $_POST["categoria"] ?? null,
        FILTER_VALIDATE_INT
    );
    $precio = $_POST["precio"] ?? "";
    $duracion = trim($_POST["duracion"] ?? "");

    // Validar campos obligatorios.
    if (
        $titulo === "" ||
        $descripcion === "" ||
        !$categoria ||
        $precio === "" ||
        $duracion === ""
    ) {
        responder(400, [
            "error" => true,
            "mensaje" => "Todos los campos son obligatorios."
        ]);
    }

    // Validar longitudes.
    if (mb_strlen($titulo) > 150 || mb_strlen($duracion) > 100) {
        responder(400, [
            "error" => true,
            "mensaje" => "El título o la duración exceden el límite permitido."
        ]);
    }

    // Validar precio.
    if (!is_numeric($precio) || (float) $precio < 0) {
        responder(400, [
            "error" => true,
            "mensaje" => "El precio no es válido."
        ]);
    }

    $precio = (float) $precio;

    // El instructor será el maestro que inició sesión.
    $instructor = (int) $_SESSION["usuario_id"];

    // Comprobar que la categoría exista.
    if (!$cursoController->comprobarCategoria($categoria)) {
        responder(400, [
            "error" => true,
            "mensaje" => "La categoría seleccionada no existe."
        ]);
    }

    // Comprobar que el usuario sea un maestro.
    if (!$cursoController->comprobarInstructor($instructor)) {
        responder(403, [
            "error" => true,
            "mensaje" => "El usuario no tiene un rol de maestro."
        ]);
    }

    // La imagen es opcional.
    $imagen = null;

    // Procesar imagen si se seleccionó una.
    if (
        isset($_FILES["imagen"]) &&
        $_FILES["imagen"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES["imagen"]["error"] !== UPLOAD_ERR_OK) {
            responder(400, [
                "error" => true,
                "mensaje" => "No fue posible recibir la imagen."
            ]);
        }

        // Comprobar tamaño máximo: 5 MB.
        if ($_FILES["imagen"]["size"] > 5 * 1024 * 1024) {
            responder(400, [
                "error" => true,
                "mensaje" => "La imagen no debe superar los 5 MB."
            ]);
        }

        // Comprobar el tipo real del archivo.
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $tipo = $finfo->file($_FILES["imagen"]["tmp_name"]);

        $tiposPermitidos = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp"
        ];

        if (!isset($tiposPermitidos[$tipo])) {
            responder(400, [
                "error" => true,
                "mensaje" => "El formato de imagen no está permitido."
            ]);
        }

        $extension = $tiposPermitidos[$tipo];

        // Crear nombre único para la imagen.
        $nombreImagen = bin2hex(random_bytes(16)) . "." . $extension;

        // Directorio donde se guardarán las imágenes.
        $directorio = __DIR__ . "/../uploads/multimedia/";

        if (!is_dir($directorio)) {
            if (!mkdir($directorio, 0755, true)) {
                responder(500, [
                    "error" => true,
                    "mensaje" => "No fue posible crear el directorio de imágenes."
                ]);
            }
        }

        $destino = $directorio . $nombreImagen;

        if (!move_uploaded_file(
            $_FILES["imagen"]["tmp_name"],
            $destino
        )) {
            responder(500, [
                "error" => true,
                "mensaje" => "No fue posible guardar la imagen."
            ]);
        }

        // Ruta relativa que se guardará en la base de datos.
        $imagen = "uploads/multimedia/" . $nombreImagen;
    }

    // Crear el curso.
    try {

        $idCurso = $cursoController->crearCurso(
            $categoria,
            $instructor,
            $titulo,
            $precio,
            $descripcion,
            $duracion,
            $imagen
        );

        if (!$idCurso) {
            responder(500, [
                "error" => true,
                "mensaje" => "No fue posible registrar el curso."
            ]);
        }

        responder(201, [
            "error" => false,
            "mensaje" => "El curso se creó correctamente.",
            "id" => $idCurso
        ]);

    } catch (Throwable $e) {

        responder(500, [
            "error" => true,
            "mensaje" => "Ocurrió un error al crear el curso."
        ]);
    }
}

// POST: ACTUALIZAR IMAGEN DE PORTADA

if (
    $metodo === "POST" &&
    ($_POST["accion"] ?? "") === "actualizar_imagen"
) {

    // Comprobar sesión
    if (
        !isset($_SESSION["usuario_id"]) ||
        !isset($_SESSION["usuario_rol"])
    ) {
        responder(401, [
            "error" => true,
            "mensaje" => "Debes iniciar sesión."
        ]);
    }

    $usuarioId = (int)$_SESSION["usuario_id"];
    $rolUsuario = (int)$_SESSION["usuario_rol"];

    // Solo administrador o maestro
    if (!in_array($rolUsuario, [1, 2], true)) {
        responder(403, [
            "error" => true,
            "mensaje" => "No tienes permiso para modificar imágenes de cursos."
        ]);
    }

    // Comprobar ID del curso
    $id = filter_var(
        $_POST["id"] ?? null,
        FILTER_VALIDATE_INT
    );

    if ($id === false || $id === null || $id <= 0) {
        responder(400, [
            "error" => true,
            "mensaje" => "El ID del curso no es válido."
        ]);
    }

    try {

        // Comprobar que el curso existe
        $cursoExistente = $cursoController->obtenerPorId($id);

        if ($cursoExistente === null) {
            responder(404, [
                "error" => true,
                "mensaje" => "Curso no encontrado."
            ]);
        }

        // Los maestros solo pueden modificar sus cursos.
        // El administrador puede modificar cualquier curso.
        if (
            $rolUsuario === 2 &&
            !$cursoController->perteneceAMaestro($id, $usuarioId)
        ) {
            responder(403, [
                "error" => true,
                "mensaje" => "No tienes permiso para modificar este curso."
            ]);
        }

        // Comprobar que se haya enviado una imagen
        if (
            !isset($_FILES["imagen"]) ||
            $_FILES["imagen"]["error"] === UPLOAD_ERR_NO_FILE
        ) {
            responder(400, [
                "error" => true,
                "mensaje" => "Debes seleccionar una imagen."
            ]);
        }

        $archivo = $_FILES["imagen"];

        if ($archivo["error"] !== UPLOAD_ERR_OK) {
            responder(400, [
                "error" => true,
                "mensaje" => "Ocurrió un problema al subir la imagen."
            ]);
        }

        // Validar tamaño máximo: 5 MB
        if ($archivo["size"] > 5 * 1024 * 1024) {
            responder(400, [
                "error" => true,
                "mensaje" => "La imagen no debe superar los 5 MB."
            ]);
        }

        // Validar tipo MIME real
        $tiposPermitidos = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp"
        ];

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $tipoMime = $finfo->file($archivo["tmp_name"]);

        if (!isset($tiposPermitidos[$tipoMime])) {
            responder(400, [
                "error" => true,
                "mensaje" => "Solo se permiten imágenes JPG, PNG o WEBP."
            ]);
        }

        // Preparar carpeta
        $carpeta = __DIR__ . "/../uploads/multimedia/";

        if (
            !is_dir($carpeta) &&
            !mkdir($carpeta, 0755, true)
        ) {
            responder(500, [
                "error" => true,
                "mensaje" => "No fue posible preparar la carpeta de imágenes."
            ]);
        }

        // Generar nombre único
        $nombreArchivo = uniqid("portada_", true)
            . "."
            . $tiposPermitidos[$tipoMime];

        $rutaDestino = $carpeta . $nombreArchivo;

        if (!move_uploaded_file($archivo["tmp_name"], $rutaDestino)) {
            responder(500, [
                "error" => true,
                "mensaje" => "No fue posible guardar la imagen."
            ]);
        }

        $imagenNueva = "uploads/multimedia/" . $nombreArchivo;
        $imagenAnterior = $cursoExistente["imagen"] ?? "";

        // Actualizar según el rol
        if ($rolUsuario === 1) {

            // Administrador: cualquier curso
            $actualizado = $cursoController->actualizarImagenAdmin(
                $id,
                $imagenNueva
            );

        } else {

            // Maestro: solamente sus propios cursos
            $actualizado = $cursoController->actualizarImagen(
                $id,
                $usuarioId,
                $imagenNueva
            );
        }

        if (!$actualizado) {
            throw new Exception("No fue posible actualizar la imagen.");
        }

        // Eliminar imagen anterior si era una portada subida
        if (
            $imagenAnterior !== "" &&
            strpos($imagenAnterior, "uploads/multimedia/") === 0
        ) {
            $rutaAnterior = __DIR__ . "/../" . $imagenAnterior;

            if (
                is_file($rutaAnterior) &&
                realpath($rutaAnterior) !== realpath($rutaDestino)
            ) {
                unlink($rutaAnterior);
            }
        }

        // Obtener curso actualizado
        $cursoActualizado = $cursoController->obtenerPorId($id);

        responder(200, [
            "error" => false,
            "mensaje" => "Imagen de portada actualizada correctamente.",
            "curso" => $cursoActualizado
        ]);

    } catch (Throwable $e) {

        error_log("Error al actualizar imagen: " . $e->getMessage());

        if (isset($rutaDestino) && is_file($rutaDestino)) {
            unlink($rutaDestino);
        }

        responder(500, [
            "error" => true,
            "mensaje" => "No fue posible actualizar la imagen de portada."
        ]);
    }
}



// PUT: ACTUALIZAR CURSO

if ($metodo === "PUT") {

    // Comprobar sesión
    if (
        !isset($_SESSION["usuario_id"]) ||
        !isset($_SESSION["usuario_rol"])
    ) {
        responder(401, [
            "error" => true,
            "mensaje" => "Debes iniciar sesión."
        ]);
    }

    $usuarioId = (int)$_SESSION["usuario_id"];
    $rolUsuario = (int)$_SESSION["usuario_rol"];

    // Solo administrador o maestro
    if (!in_array($rolUsuario, [1, 2], true)) {
        responder(403, [
            "error" => true,
            "mensaje" => "No tienes permiso para actualizar cursos."
        ]);
    }

    // Comprobar ID
    if (!isset($_GET["id"])) {
        responder(400, [
            "error" => true,
            "mensaje" => "Debe proporcionar el parámetro id."
        ]);
    }

    $id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

    if ($id === false || $id === null || $id <= 0) {
        responder(400, [
            "error" => true,
            "mensaje" => "El parámetro id debe ser un número entero positivo."
        ]);
    }

    try {

        // Comprobar que el curso existe
        $cursoExistente = $cursoController->obtenerPorId($id);

        if ($cursoExistente === null) {
            responder(404, [
                "error" => true,
                "mensaje" => "Curso no encontrado."
            ]);
        }

        // Los maestros solo pueden modificar sus cursos.
        // El administrador puede modificar cualquier curso.
        if (
            $rolUsuario === 2 &&
            !$cursoController->perteneceAMaestro($id, $usuarioId)
        ) {
            responder(403, [
                "error" => true,
                "mensaje" => "No tienes permiso para modificar este curso."
            ]);
        }

        // Leer JSON
        $datos = json_decode(
            file_get_contents("php://input"),
            true
        );

        if (!is_array($datos)) {
            responder(400, [
                "error" => true,
                "mensaje" => "Los datos enviados no tienen un formato JSON válido."
            ]);
        }

        // Campos obligatorios
        $camposObligatorios = [
            "titulo",
            "descripcion",
            "categoria",
            "precio",
            "duracion"
        ];

        foreach ($camposObligatorios as $campo) {

            if (
                !isset($datos[$campo]) ||
                !is_string($datos[$campo]) && $campo !== "precio" && $campo !== "categoria" ||
                $datos[$campo] === ""
            ) {
                responder(400, [
                    "error" => true,
                    "mensaje" => "Falta el campo obligatorio: " . $campo
                ]);
            }
        }

        // Limpiar textos
        $titulo = trim($datos["titulo"]);
        $descripcion = trim($datos["descripcion"]);
        $duracion = trim($datos["duracion"]);

        if (
            $titulo === "" ||
            $descripcion === "" ||
            $duracion === ""
        ) {
            responder(400, [
                "error" => true,
                "mensaje" => "Los campos de texto no pueden estar vacíos."
            ]);
        }

        // Validar precio
        if (
            !is_numeric($datos["precio"]) ||
            (float)$datos["precio"] < 0
        ) {
            responder(400, [
                "error" => true,
                "mensaje" => "El precio debe ser un número mayor o igual a 0."
            ]);
        }

        // Validar categoría
        $categoria = filter_var(
            $datos["categoria"],
            FILTER_VALIDATE_INT
        );

        if ($categoria === false || $categoria <= 0) {
            responder(400, [
                "error" => true,
                "mensaje" => "La categoría debe ser un ID válido."
            ]);
        }

        if (!$cursoController->comprobarCategoria($categoria)) {
            responder(400, [
                "error" => true,
                "mensaje" => "La categoría indicada no existe."
            ]);
        }

        // Conservar el maestro originalmente asignado.
        // El administrador tampoco puede cambiarlo.
        $instructor = (int)$cursoExistente["instructorId"];

        if (!$cursoController->comprobarInstructor($instructor)) {
            responder(400, [
                "error" => true,
                "mensaje" => "El maestro asignado al curso no es válido."
            ]);
        }

        $precio = (float)$datos["precio"];

        // La imagen se actualiza por separado.
        // Conservar siempre la portada actual en esta operación.
        $imagen = $cursoExistente["imagen"];

        // Actualizar curso
        $cursoController->actualizarCurso(
            $id,
            $categoria,
            $instructor,
            $titulo,
            $precio,
            $descripcion,
            $duracion,
            $imagen
        );

        // Obtener curso actualizado
        $cursoActualizado = $cursoController->obtenerPorId($id);

        responder(200, [
            "error" => false,
            "mensaje" => "Curso actualizado correctamente.",
            "curso" => $cursoActualizado
        ]);

    } catch (Throwable $e) {

        error_log("Error al actualizar curso: " . $e->getMessage());

        responder(500, [
            "error" => true,
            "mensaje" => "No fue posible actualizar el curso."
        ]);
    }
}

// DELETE: cambiar el estado del curso
if ($metodo === "DELETE") {

    // Comprobar sesión y rol
    if (
        !isset($_SESSION["usuario_id"]) ||
        !isset($_SESSION["usuario_rol"])
    ) {
        responder(401, [
            "error" => true,
            "mensaje" => "Debes iniciar sesión."
        ]);
    }

    if ((int)$_SESSION["usuario_rol"] !== 2) {
        responder(403, [
            "error" => true,
            "mensaje" => "Solo los maestros pueden cambiar el estado de sus cursos."
        ]);
    }

    $maestroId = (int)$_SESSION["usuario_id"];

    // Comprobar ID
    if (!isset($_GET["id"])) {
        responder(400, [
            "error" => true,
            "mensaje" => "Debe proporcionar el id del curso."
        ]);
    }

    $id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

    if ($id === false || $id === null || $id <= 0) {
        responder(400, [
            "error" => true,
            "mensaje" => "El parametro id debe ser un numero entero positivo."
        ]);
    }

    // Comprobar el nuevo estado
    if (!isset($_GET["estado"])) {
        responder(400, [
            "error" => true,
            "mensaje" => "Debe proporcionar el nuevo estado del curso."
        ]);
    }

    $estado = filter_input(INPUT_GET, "estado", FILTER_VALIDATE_INT);

    if (
        $estado === false ||
        $estado === null ||
        !in_array($estado, [0, 1], true)
    ) {
        responder(400, [
            "error" => true,
            "mensaje" => "El estado debe ser 0 o 1."
        ]);
    }

    try {

        // Comprobar que el curso existe
        $cursoExistente = $cursoController->obtenerPorId($id);

        if ($cursoExistente === null) {
            responder(404, [
                "error" => true,
                "mensaje" => "Curso no encontrado."
            ]);
        }

        // Comprobar propiedad del curso
        if (!$cursoController->perteneceAMaestro($id, $maestroId)) {
            responder(403, [
                "error" => true,
                "mensaje" => "No tienes permiso para modificar este curso."
            ]);
        }

        // Cambiar el estado
        $cursoController->cambiarEstadoCurso($id, $estado);

        responder(200, [
            "error" => false,
            "mensaje" => $estado === 1
                ? "El curso se reactivó correctamente."
                : "El curso se dio de baja correctamente.",
            "estado" => $estado
        ]);

    } catch (Throwable $e) {

        responder(500, [
            "error" => true,
            "mensaje" => "Error interno del servidor."
        ]);
    }
}

// 405
responder(405, [
    "error" => true,
    "mensaje" => "Metodo HTTP no permitido."
]);

?>