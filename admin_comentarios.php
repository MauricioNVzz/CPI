<?php

require_once __DIR__ . "/middleware/auth.php";

verificarAcceso([1]);

require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/controllers/ComentarioController.php";

$comentarioController = new ComentarioController($conexion);

// ==========================================
// PROTECCIÓN CSRF
// ==========================================

if (empty($_SESSION["csrf_admin_comentarios"])) {
    $_SESSION["csrf_admin_comentarios"] = bin2hex(random_bytes(32));
}

$mensaje = "";
$tipoMensaje = "";

// ==========================================
// PROCESAR ELIMINACIÓN
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        if (
            !isset($_POST["csrf_token"]) ||
            !hash_equals(
                $_SESSION["csrf_admin_comentarios"],
                $_POST["csrf_token"]
            )
        ) {
            throw new RuntimeException(
                "La solicitud no es válida. Recarga la página."
            );
        }

        $comentarioId = $_POST["comentario_id"] ?? 0;
        $motivo = $_POST["motivo"] ?? "";

        $adminId = (int) $_SESSION["usuario_id"];

        $comentarioController->eliminarConMotivo(
            $comentarioId,
            $adminId,
            $motivo
        );

        $mensaje = "Comentario eliminado y registrado en el historial.";
        $tipoMensaje = "exito";

    } catch (InvalidArgumentException $e) {

        $mensaje = $e->getMessage();
        $tipoMensaje = "error";

    } catch (RuntimeException $e) {

        $mensaje = $e->getMessage();
        $tipoMensaje = "error";

    } catch (Throwable $e) {

        error_log($e->getMessage());

        $mensaje = "Ocurrió un error al eliminar el comentario.";
        $tipoMensaje = "error";
    }
}


// ==========================================
// CONSULTAR COMENTARIOS
// ==========================================

try {

    $comentarios = $comentarioController->obtenerTodos();

} catch (Throwable $e) {

    error_log($e->getMessage());

    $comentarios = [];

    $mensaje = "No fue posible cargar los comentarios.";
    $tipoMensaje = "error";
}


// ==========================================
// FUNCIÓN PARA ESCAPAR TEXTO HTML
// ==========================================

function escapar($valor)
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        "UTF-8"
    );
}


?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Administrar comentarios</title>

    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/admin_comentarios.css">

</head>

<body>

    <?php require_once __DIR__ . "/includes/navbar.php"; ?>

    <main class="contenedor-admin-comentarios">

        <header class="encabezado">

            <h1>Administración de comentarios</h1>

            <p>
                Consulta y administra los comentarios publicados en los cursos.
            </p>

        </header>

        <?php if ($mensaje !== ""): ?>

            <div class="mensaje <?php echo escapar($tipoMensaje); ?>">

                <?php echo escapar($mensaje); ?>

            </div>

        <?php endif; ?>

        <section class="panel-comentarios">

            <h2>Comentarios publicados</h2>

            <div class="tabla-contenedor">

                <table>

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Alumno</th>
                            <th>Curso</th>
                            <th>Contenido multimedia</th>
                            <th>Comentario</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if (empty($comentarios)): ?>

                            <tr>
                                <td colspan="7">
                                    No hay comentarios registrados.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($comentarios as $comentario): ?>

                                <tr>

                                    <td>
                                        <?php echo escapar($comentario["Comentario_id"]); ?>
                                    </td>

                                    <td>
                                        <?php echo escapar($comentario["Usuario_nombre"]); ?>
                                    </td>

                                    <td>
                                        <?php echo escapar($comentario["Curso_nombre"]); ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?php echo escapar($comentario["Multimedia_titulo"]); ?>
                                        </strong>

                                        <small>
                                            <?php echo escapar($comentario["Multimedia_tipo"]); ?>
                                        </small>
                                    </td>

                                    <td class="contenido-comentario">
                                        <?php echo nl2br(escapar($comentario["Comentario_contenido"])); ?>
                                    </td>

                                    <td>
                                        <?php echo escapar($comentario["Comentario_fecha"]); ?>
                                    </td>

                                    <td>

                                        <form
                                            method="POST"
                                            class="formulario-eliminar"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?php echo escapar($_SESSION["csrf_admin_comentarios"]); ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="comentario_id"
                                                value="<?php echo escapar($comentario["Comentario_id"]); ?>"
                                            >

                                            <label>
                                                Motivo de eliminación
                                            </label>

                                            <textarea
                                                name="motivo"
                                                maxlength="500"
                                                required
                                                placeholder="Escribe el motivo..."
                                            ></textarea>

                                            <button
                                                type="submit"
                                                onclick="return confirm('¿Deseas eliminar este comentario?');"
                                                class="btn-eliminar"
                                            >
                                                Eliminar comentario
                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</body>

</html>
