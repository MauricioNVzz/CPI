<?php

require_once __DIR__ . "/middleware/auth.php";

verificarAcceso([1]);

require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/controllers/CategoriaController.php";

$categoriaController = new CategoriaController($conexion);

// ==========================================
// PROTECCIÓN CSRF
// ==========================================

if (empty($_SESSION["csrf_admin_categorias"])) {
    $_SESSION["csrf_admin_categorias"] = bin2hex(random_bytes(32));
}

$mensaje = "";
$tipoMensaje = "";

// ==========================================
// PROCESAR FORMULARIOS
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        if (
            !isset($_POST["csrf_token"]) ||
            !hash_equals(
                $_SESSION["csrf_admin_categorias"],
                $_POST["csrf_token"]
            )
        ) {
            throw new RuntimeException(
                "La solicitud no es válida. Recarga la página."
            );
        }

        $accion = $_POST["accion"] ?? "";

        switch ($accion) {

            case "crear":

                $nombre = $_POST["nombre"] ?? "";

                $categoriaController->crear($nombre);

                $mensaje = "Categoría registrada correctamente.";
                $tipoMensaje = "exito";

                break;

            case "actualizar":

                $id = $_POST["categoria_id"] ?? 0;
                $nombre = $_POST["nombre"] ?? "";

                $categoriaController->actualizar($id, $nombre);

                $mensaje = "Categoría actualizada correctamente.";
                $tipoMensaje = "exito";

                break;

            case "eliminar":

                $id = $_POST["categoria_id"] ?? 0;

                $categoriaController->eliminar($id);

                $mensaje = "Categoría eliminada correctamente.";
                $tipoMensaje = "exito";

                break;

            default:

                throw new InvalidArgumentException(
                    "La operación solicitada no es válida."
                );
        }

    } catch (InvalidArgumentException $e) {

        $mensaje = $e->getMessage();
        $tipoMensaje = "error";

    } catch (mysqli_sql_exception $e) {

        if ($e->getCode() === 1451) {
            $mensaje = "No puedes eliminar esta categoría porque tiene cursos asociados.";
        } else {
            error_log($e->getMessage());
            $mensaje = "Ocurrió un error al procesar la categoría.";
        }

        $tipoMensaje = "error";

    } catch (Throwable $e) {

        error_log($e->getMessage());

        $mensaje = $e->getMessage() ===
            "La solicitud no es válida. Recarga la página."
            ? $e->getMessage()
            : "Ocurrió un error al procesar la solicitud.";

        $tipoMensaje = "error";
    }
}

// ==========================================
// OBTENER CATEGORÍAS
// ==========================================

try {

    $categorias = $categoriaController->obtenerTodas();

} catch (Throwable $e) {

    error_log($e->getMessage());

    $categorias = [];

    $mensaje = "No fue posible cargar las categorías.";
    $tipoMensaje = "error";
}

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

    <title>Administrar categorías</title>

    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/admin_categorias.css">

</head>

<body>

    <?php require_once __DIR__ . "/includes/navbar.php"; ?>

    <main class="contenedor-admin-categorias">

        <header class="encabezado">

            <h1>Administración de categorías</h1>

            <p>
                Gestiona las categorías disponibles en la plataforma.
            </p>

        </header>

        <?php if ($mensaje !== ""): ?>

            <div class="mensaje <?php echo escapar($tipoMensaje); ?>">

                <?php echo escapar($mensaje); ?>

            </div>

        <?php endif; ?>

        <!-- ================================= -->
        <!-- FORMULARIO PARA CREAR CATEGORÍA -->
        <!-- ================================= -->

        <section class="panel-categorias">

            <h2>Registrar nueva categoría</h2>

            <form method="POST" class="formulario-categoria">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo escapar($_SESSION["csrf_admin_categorias"]); ?>"
                >

                <input
                    type="hidden"
                    name="accion"
                    value="crear"
                >

                <div class="campo">

                    <label for="nombre">
                        Nombre de la categoría
                    </label>

                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        maxlength="100"
                        placeholder="Ej. Programación"
                        required
                    >

                </div>

                <button type="submit" class="btn-guardar">
                    Registrar categoría
                </button>

            </form>

        </section>

        <!-- ================================= -->
        <!-- LISTADO DE CATEGORÍAS -->
        <!-- ================================= -->

        <section class="panel-categorias">

            <h2>Categorías registradas</h2>

            <div class="tabla-contenedor">

                <table>

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Cursos asociados</th>
                            <th>Acciones</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if (empty($categorias)): ?>

                            <tr>
                                <td colspan="4">
                                    No hay categorías registradas.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($categorias as $categoria): ?>

                                <tr>

                                    <td>
                                        <?php echo escapar($categoria["Categoria_id"]); ?>
                                    </td>

                                    <td>

                                        <form
                                            method="POST"
                                            class="formulario-editar"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?php echo escapar($_SESSION["csrf_admin_categorias"]); ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="accion"
                                                value="actualizar"
                                            >

                                            <input
                                                type="hidden"
                                                name="categoria_id"
                                                value="<?php echo escapar($categoria["Categoria_id"]); ?>"
                                            >

                                            <input
                                                type="text"
                                                name="nombre"
                                                value="<?php echo escapar($categoria["Categoria_nombre"]); ?>"
                                                maxlength="100"
                                                required
                                            >

                                    </td>

                                    <td>
                                        <?php echo escapar($categoria["total_cursos"]); ?>
                                    </td>

                                    <td class="acciones">

                                            <button
                                                type="submit"
                                                class="btn-editar"
                                            >
                                                Guardar cambios
                                            </button>

                                        </form>

                                        <form
                                            method="POST"
                                            onsubmit="return confirm('¿Estás seguro de eliminar esta categoría?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?php echo escapar($_SESSION["csrf_admin_categorias"]); ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="accion"
                                                value="eliminar"
                                            >

                                            <input
                                                type="hidden"
                                                name="categoria_id"
                                                value="<?php echo escapar($categoria["Categoria_id"]); ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn-eliminar"
                                            >
                                                Eliminar
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
