<?php

session_start();

include "conexion.php";

require_once "controllers/CursoController.php";
require_once "controllers/MultimediaController.php";


// ==========================================
// COMPROBAR SESIÓN
// ==========================================

if (!isset($_SESSION["usuario_id"])) {

    header("Location: index.php");
    exit;
}


// ==========================================
// COMPROBAR QUE SEA MAESTRO
// ==========================================

if (
    !isset($_SESSION["usuario_rol"]) ||
    (int)$_SESSION["usuario_rol"] !== 2
) {

    header("Location: cursos_inicio.php");
    exit;
}


$maestroId =
    (int)$_SESSION["usuario_id"];


// ==========================================
// OBTENER ID DEL CURSO
// ==========================================

$cursoId =
    $_GET["id"] ?? "";

if (
    !filter_var(
        $cursoId,
        FILTER_VALIDATE_INT
    ) ||
    (int)$cursoId <= 0
) {

    die("ID de curso no válido.");

}

$cursoId = (int)$cursoId;


// ==========================================
// CONTROLADORES
// ==========================================

$cursoController =
    new CursoController($conexion);

$multimediaController =
    new MultimediaController($conexion);

require_once "controllers/NivelController.php";

$nivelController =
    new NivelController($conexion);
// ==========================================
// COMPROBAR QUE EL CURSO SEA DEL MAESTRO
// ==========================================

try {
    $curso = $multimediaController->obtenerCursoDeMaestro(
        $cursoId,
        $maestroId
    );

    if ($curso === null) {
        header("Location: maestro.php");
        exit;
    }
} catch (Throwable $e) {
    error_log("Error al verificar el curso: " . $e->getMessage());

    http_response_code(500);
    exit("Ocurrió un error al verificar el curso.");
}

// ==========================================
// OBTENER NIVELES DEL CURSO
// ==========================================

try {

    $niveles =
        $nivelController->obtenerPorCurso($cursoId);

} catch (Throwable $e) {

    error_log(
        "Error al obtener los niveles: " .
        $e->getMessage()
    );

    $niveles = [];

    $mensaje =
        "No se pudieron obtener los niveles del curso.";

    $tipoMensaje = "error";
}


// ==========================================
// VARIABLES
// ==========================================

$mensaje = "";
$tipoMensaje = "";

// ==========================================
// PROCESAR CREACIÓN DE NIVELES
// ==========================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["accion"] ?? "") === "crear_nivel"
) {

    $nombreNivel = trim(
        $_POST["nombre_nivel"] ?? ""
    );

    $descripcionNivel = trim(
        $_POST["descripcion_nivel"] ?? ""
    );

    $ordenNivel = $_POST["orden_nivel"] ?? "";

    if ($nombreNivel === "") {

        $mensaje = "El nombre del nivel es obligatorio.";
        $tipoMensaje = "error";

    } elseif (
        !filter_var(
            $ordenNivel,
            FILTER_VALIDATE_INT
        ) ||
        (int)$ordenNivel <= 0
    ) {

        $mensaje = "El orden debe ser un número entero mayor que cero.";
        $tipoMensaje = "error";

    } else {

        try {

            $nivelController->crear(
                $cursoId,
                $nombreNivel,
                $descripcionNivel,
                (int)$ordenNivel
            );

            $mensaje = "El nivel se creó correctamente.";
            $tipoMensaje = "exito";

            // Actualizar los niveles mostrados.
            $niveles = $nivelController->obtenerPorCurso($cursoId);

        } catch (Throwable $e) {

            error_log(
                "Error al crear nivel: " . $e->getMessage()
            );

            $mensaje = "No se pudo crear el nivel.";
            $tipoMensaje = "error";
        }
    }
}

// ==========================================
// PROCESAR EDICIÓN DE NIVELES
// ==========================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["accion"] ?? "") === "editar_nivel"
) {

    $nivelIdEditar = filter_input(
        INPUT_POST,
        "nivel_id",
        FILTER_VALIDATE_INT
    );

    $nombreNivelEditar = trim(
        $_POST["nombre_nivel"] ?? ""
    );

    $descripcionNivelEditar = trim(
        $_POST["descripcion_nivel"] ?? ""
    );

    $ordenNivelEditar = $_POST["orden_nivel"] ?? "";

    if (
        $nivelIdEditar === false ||
        $nivelIdEditar === null ||
        $nivelIdEditar <= 0 ||
        $nombreNivelEditar === ""
    ) {

        $mensaje = "Los datos del nivel no son válidos.";
        $tipoMensaje = "error";

    } elseif (
        !filter_var(
            $ordenNivelEditar,
            FILTER_VALIDATE_INT
        ) ||
        (int)$ordenNivelEditar <= 0
    ) {

        $mensaje = "El orden debe ser un número entero mayor que cero.";
        $tipoMensaje = "error";

    } else {

        try {

            $nivelController->actualizar(
                (int)$nivelIdEditar,
                $cursoId,
                $nombreNivelEditar,
                $descripcionNivelEditar,
                (int)$ordenNivelEditar
            );

            $mensaje = "El nivel se actualizó correctamente.";
            $tipoMensaje = "exito";

            // Actualizar los niveles mostrados.
            $niveles = $nivelController->obtenerPorCurso($cursoId);

        } catch (Throwable $e) {

            error_log(
                "Error al actualizar nivel: " .
                $e->getMessage()
            );

            $mensaje = "No se pudo actualizar el nivel.";
            $tipoMensaje = "error";
        }
    }
}

// ==========================================
// PROCESAR FORMULARIO
// ==========================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    !in_array(
        ($_POST["accion"] ?? ""),
        ["crear_nivel", "editar_nivel"],
        true
    )
) {

    $titulo =
        trim(
            $_POST["titulo"] ?? ""
        );

        $nivelId = filter_input(
    INPUT_POST,
    "nivel",
    FILTER_VALIDATE_INT
);

    $descripcion =
        trim(
            $_POST["descripcion"] ?? ""
        );

    $tipo =
        $_POST["tipo"] ?? "";

    $orden =
        $_POST["orden"] ?? 1;

    $gratuito = 0;

$gratuito = isset($_POST["gratuito"]) ? 1 : 0;

$nivelValido = false;

foreach ($niveles as $nivel) {

    if ((int)$nivel["id"] === (int)$nivelId) {

        $nivelValido = true;
        break;

    }
}
    // ======================================
    // VALIDAR DATOS
    // ======================================

    if (
    $titulo === "" ||
    $tipo === "" ||
    $nivelId === false ||
    $nivelId === null ||
    $nivelId <= 0 ||
    !$nivelValido
) {

        $mensaje =
            "El título y el tipo son obligatorios.";

        $tipoMensaje =
            "error";

    } elseif (
        !in_array(
            $tipo,
            ["video", "imagen", "pdf"],
            true
        )
    ) {

        $mensaje =
            "El tipo de multimedia no es válido.";

        $tipoMensaje =
            "error";

    } elseif (
        !filter_var(
            $orden,
            FILTER_VALIDATE_INT
        ) ||
        (int)$orden <= 0
    ) {

        $mensaje =
            "El orden debe ser un número entero mayor que cero.";

        $tipoMensaje =
            "error";

    } elseif (
        !isset(
            $_FILES["archivo"]
        ) ||
        $_FILES["archivo"]["error"] !== UPLOAD_ERR_OK
    ) {

        $mensaje =
            "Debes seleccionar un archivo.";

        $tipoMensaje =
            "error";

    } else {

        $archivoSubido =
            $_FILES["archivo"];


        // ==================================
        // EXTENSIONES PERMITIDAS
        // ==================================

        $extensionesPermitidas = [
            "video" => [
                "mp4",
                "webm",
                "ogg"
            ],

            "imagen" => [
                "jpg",
                "jpeg",
                "png",
                "webp"
            ],

            "pdf" => [
                "pdf"
            ]
        ];


        $nombreOriginal =
            $archivoSubido["name"];

        $extension =
            strtolower(
                pathinfo(
                    $nombreOriginal,
                    PATHINFO_EXTENSION
                )
            );


        if (
            !in_array(
                $extension,
                $extensionesPermitidas[$tipo],
                true
            )
        ) {

            $mensaje =
                "El formato del archivo no corresponde al tipo seleccionado.";

            $tipoMensaje =
                "error";

        } else {

            // ==============================
            // GENERAR NOMBRE ÚNICO
            // ==============================

            $nombreArchivo =
                uniqid(
                    "multimedia_",
                    true
                )
                . "."
                . $extension;


            $carpeta =
                "uploads/multimedia/";

            $ruta =
                $carpeta .
                $nombreArchivo;


            // ==============================
            // MOVER ARCHIVO
            // ==============================

            if (
                move_uploaded_file(
                    $archivoSubido["tmp_name"],
                    $ruta
                )
            ) {

                try {

                    $multimediaController
    ->agregar(
        $cursoId,
        $nivelId,
        $tipo,
        $ruta,
        $titulo,
        $descripcion,
        (int)$orden,
        $gratuito
    );


                    $mensaje =
                        "El contenido se agregó correctamente.";

                    $tipoMensaje =
                        "exito";

                } catch (Throwable $e) {

                    // Si falló MySQL,
                    // eliminamos el archivo
                    // que acabamos de subir.

                    if (
                        file_exists($ruta)
                    ) {

                        unlink($ruta);

                    }

                    $mensaje =
                        "Ocurrió un error al guardar el contenido.";

                    $tipoMensaje =
                        "error";
                }

            } else {

                $mensaje =
                    "No se pudo guardar el archivo.";

                $tipoMensaje =
                    "error";
            }
        }
    }
}


// ==========================================
// OBTENER MULTIMEDIA
// ==========================================

try {

    $multimedia =
        $multimediaController
            ->obtenerPorCurso($cursoId);

} catch (Throwable $e) {

    $multimedia = [];

    $mensaje =
        "No se pudo obtener el contenido.";

    $tipoMensaje =
        "error";
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Administrar contenido
    </title>

    <link
        rel="stylesheet"
        href="css/multimedia.css"
    >

</head>

<body>

    <!-- ==================================
         BARRA
         ================================== -->

    <header class="barra-navegacion">

        <div class="logo">

            <a href="maestro.php">
                CPI
            </a>

        </div>

        <nav>

            <a href="maestro.php">
                Mis cursos
            </a>

            <a href="cursos_inicio.php">
                Inicio
            </a>

            <a href="cerrar_sesion.php">
                Cerrar sesión
            </a>

        </nav>

    </header>


    <main class="contenedor">

        <!-- ==================================
             ENCABEZADO
             ================================== -->

        <section class="encabezado">

            <a
                href="maestro.php"
                class="regresar"
            >
                ← Regresar a mis cursos
            </a>

            <h1>

                <?php
                echo htmlspecialchars(
                    $curso["Curso_nombre"]
                );
                ?>

            </h1>

            <p>
                Administración de contenido
            </p>

        </section>


        <!-- ==================================
             MENSAJE
             ================================== -->

        <?php if ($mensaje !== ""): ?>

            <div
                class="mensaje
                <?php
                echo $tipoMensaje;
                ?>"
            >

                <?php
                echo htmlspecialchars(
                    $mensaje
                );
                ?>

            </div>

        <?php endif; ?>

        <!-- ==================================
     ADMINISTRAR NIVELES
     ================================== -->

<section class="formulario-contenedor">

    <h2>
        Crear nivel
    </h2>

    <form method="POST">

        <input
            type="hidden"
            name="accion"
            value="crear_nivel"
        >

        <label for="nombre_nivel">
            Nombre del nivel
        </label>

        <input
            type="text"
            id="nombre_nivel"
            name="nombre_nivel"
            maxlength="150"
            required
        >

        <label for="descripcion_nivel">
            Descripción
        </label>

        <textarea
            id="descripcion_nivel"
            name="descripcion_nivel"
            rows="4"
        ></textarea>

        <label for="orden_nivel">
            Orden del nivel
        </label>

        <input
            type="number"
            id="orden_nivel"
            name="orden_nivel"
            min="1"
            value="1"
            required
        >

        <button type="submit">
            Crear nivel
        </button>

    </form>

</section>

<!-- ==================================
     LISTA Y EDICIÓN DE NIVELES
     ================================== -->

<section class="contenido-existente">

    <h2>Administrar niveles</h2>

    <?php if (count($niveles) > 0): ?>

        <?php foreach ($niveles as $nivel): ?>

            <article class="elemento-multimedia">

                <form method="POST">

                    <input
                        type="hidden"
                        name="accion"
                        value="editar_nivel"
                    >

                    <input
                        type="hidden"
                        name="nivel_id"
                        value="<?php echo (int)$nivel["id"]; ?>"
                    >

                    <label>
                        Nombre del nivel
                    </label>

                    <input
                        type="text"
                        name="nombre_nivel"
                        maxlength="150"
                        value="<?php echo htmlspecialchars(
                            $nivel["nombre"],
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>"
                        required
                    >

                    <label>
                        Descripción
                    </label>

                    <textarea
                        name="descripcion_nivel"
                        rows="3"
                    ><?php echo htmlspecialchars(
                        $nivel["descripcion"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?></textarea>

                    <label>
                        Orden
                    </label>

                    <input
                        type="number"
                        name="orden_nivel"
                        min="1"
                        value="<?php echo (int)$nivel["orden"]; ?>"
                        required
                    >

                    <button type="submit">
                        Guardar cambios
                    </button>

                </form>

            </article>

        <?php endforeach; ?>

    <?php else: ?>

        <p>Este curso todavía no tiene niveles.</p>

    <?php endif; ?>

</section>

        <!-- ==================================
             FORMULARIO
             ================================== -->

        <section class="formulario-contenedor">

            <h2>
                Agregar multimedia
            </h2>

            <form
                method="POST"
                enctype="multipart/form-data"
            >

                <label for="titulo">
                    Título
                </label>

                <input
                    type="text"
                    id="titulo"
                    name="titulo"
                    required
                >

                <label for="nivel">
    Nivel del curso
</label>

<select
    id="nivel"
    name="nivel"
    required
>

    <option value="">
        Selecciona un nivel
    </option>

    <?php foreach ($niveles as $nivel): ?>

        <option value="<?php echo (int)$nivel["id"]; ?>">

            <?php
            echo htmlspecialchars(
                $nivel["nombre"],
                ENT_QUOTES,
                "UTF-8"
            );
            ?>

        </option>

    <?php endforeach; ?>

</select>


                <label for="tipo">
                    Tipo de contenido
                </label>

                <select
                    id="tipo"
                    name="tipo"
                    required
                >

                    <option value="">
                        Selecciona un tipo
                    </option>

                    <option value="video">
                        Video
                    </option>

                    <option value="imagen">
                        Imagen
                    </option>

                    <option value="pdf">
                        PDF
                    </option>

                </select>


                <label for="archivo">
                    Archivo
                </label>

                <input
                    type="file"
                    id="archivo"
                    name="archivo"
                    required
                >


                <label for="descripcion">
                    Descripción
                </label>

                <textarea
                    id="descripcion"
                    name="descripcion"
                    rows="4"
                ></textarea>


                <label for="orden">
                    Orden de la lección
                </label>

                <input
                    type="number"
                    id="orden"
                    name="orden"
                    min="1"
                    value="1"
                    required
                >


                <label class="opcion">

                    <input
                        type="checkbox"
                        name="gratuito"
                        value="1"
                    >

                    Contenido gratuito

                </label>


                <button type="submit">
                    Agregar contenido
                </button>

            </form>

        </section>


        <!-- ==================================
             CONTENIDO EXISTENTE
             ================================== -->

        <section class="contenido-existente">

            <h2>
                Contenido del curso
            </h2>


            <?php if (
                count($multimedia) > 0
            ): ?>

                <div class="lista-multimedia">

                    <?php foreach (
                        $multimedia
                        as $elemento
                    ): ?>

                        <article
                            class="elemento-multimedia"
                        >

                            <div class="informacion">

                                <h3>

                                    <?php
                                    echo htmlspecialchars(
                                        $elemento["titulo"]
                                    );
                                    ?>

                                </h3>

                                <p>

                                    Tipo:
                                    <?php
                                    echo htmlspecialchars(
                                        $elemento["tipo"]
                                    );
                                    ?>

                                </p>

                                <p>

                                    Orden:
                                    <?php
                                    echo $elemento["orden"];
                                    ?>

                                </p>

                                <?php if (
                                    $elemento["gratuito"]
                                ): ?>

                                    <span class="gratis">
                                        Contenido gratuito
                                    </span>

                                <?php else: ?>

                                    <span class="privado">
                                        Contenido privado
                                    </span>

                                <?php endif; ?>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="sin-contenido">

                    <p>
                        Este curso todavía no tiene
                        contenido multimedia.
                    </p>

                </div>

            <?php endif; ?>

        </section>

    </main>

</body>

</html>