<?php

require_once __DIR__ . "/middleware/auth.php";

verificarAcceso([1]);

require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/controllers/CursoController.php";

// ==========================================
// CONTROLADOR
// ==========================================

$cursoController = new CursoController($conexion);

// ==========================================
// FUNCIÓN PARA ESCAPAR TEXTO
// ==========================================

function escapar($valor)
{
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

// ==========================================
// OBTENER CURSOS Y CATEGORÍAS
// ==========================================

$error = "";
$cursos = [];
$categorias = [];

try {
    $cursos = $cursoController->obtenerTodos();
    $categorias = $cursoController->obtenerCategorias();
} catch (Throwable $e) {
    error_log("Error en admin_cursos.php: " . $e->getMessage());
    $error = "No fue posible cargar los cursos.";
}

// ==========================================
// FILTROS
// ==========================================

$busqueda = isset($_GET["busqueda"]) && is_string($_GET["busqueda"])
    ? trim($_GET["busqueda"])
    : "";

$filtroCategoria = isset($_GET["categoria"]) && is_string($_GET["categoria"])
    ? trim($_GET["categoria"])
    : "";

$filtroMaestro = isset($_GET["maestro"]) && is_string($_GET["maestro"])
    ? trim($_GET["maestro"])
    : "";

// ==========================================
// APLICAR FILTROS
// ==========================================

$cursosFiltrados = [];

foreach ($cursos as $curso) {

    // Buscar por título
    if (
        $busqueda !== "" &&
        stripos($curso["titulo"], $busqueda) === false
    ) {
        continue;
    }

    // Filtrar por categoría
    if (
        $filtroCategoria !== "" &&
        $curso["categoria"] !== $filtroCategoria
    ) {
        continue;
    }

    // Buscar por nombre del maestro
    if (
        $filtroMaestro !== "" &&
        stripos($curso["instructor"], $filtroMaestro) === false
    ) {
        continue;
    }

    $cursosFiltrados[] = $curso;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Administrar cursos</title>

    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/admin_cursos.css">
</head>

<body>

    <?php include __DIR__ . "/includes/navbar.php"; ?>

    <main class="contenedor-admin-cursos">

        <section class="encabezado">
            <h1>Administración de cursos</h1>

            <p>
                Consulta y administra los cursos registrados
                en la plataforma.
            </p>
        </section>

        <?php if ($error !== ""): ?>

            <div class="mensaje error">
                <?= escapar($error) ?>
            </div>

        <?php else: ?>

            <!-- ==================================
                 FILTROS
            =================================== -->

            <section class="panel-cursos">

                <h2>Buscar cursos</h2>

                <form method="GET" class="formulario-filtros">

                    <div class="campo">
                        <label for="busqueda">
                            Nombre del curso
                        </label>

                        <input
                            type="text"
                            id="busqueda"
                            name="busqueda"
                            placeholder="Buscar por título..."
                            value="<?= escapar($busqueda) ?>"
                        >
                    </div>

                    <div class="campo">
                        <label for="categoria">
                            Categoría
                        </label>

                        <select id="categoria" name="categoria">

                            <option value="">
                                Todas las categorías
                            </option>

                            <?php foreach ($categorias as $categoria): ?>

                                <option
                                    value="<?= escapar($categoria["nombre"]) ?>"
                                    <?= $filtroCategoria === $categoria["nombre"]
                                        ? "selected"
                                        : "" ?>
                                >
                                    <?= escapar($categoria["nombre"]) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="campo">
                        <label for="maestro">
                            Maestro
                        </label>

                        <input
                            type="text"
                            id="maestro"
                            name="maestro"
                            placeholder="Buscar maestro..."
                            value="<?= escapar($filtroMaestro) ?>"
                        >
                    </div>

                    <div class="acciones-filtros">

                        <button type="submit" class="btn-buscar">
                            Buscar
                        </button>

                        <a href="admin_cursos.php" class="btn-limpiar">
                            Limpiar filtros
                        </a>

                    </div>

                </form>

            </section>

            <!-- ==================================
                 LISTADO DE CURSOS
            =================================== -->

            <section class="panel-cursos">

                <div class="encabezado-tabla">
                    <h2>Cursos registrados</h2>

                    <span class="total-cursos">
                        Total encontrados:
                        <?= count($cursosFiltrados) ?>
                    </span>
                </div>

                <div class="tabla-contenedor">

                    <table>

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Imagen</th>
                                <th>Curso</th>
                                <th>Categoría</th>
                                <th>Maestro</th>
                                <th>Precio</th>
                                <th>Duración</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (empty($cursosFiltrados)): ?>

                                <tr>
                                    <td colspan="8" class="sin-resultados">
                                        No se encontraron cursos.
                                    </td>
                                </tr>

                            <?php else: ?>

                                <?php foreach ($cursosFiltrados as $curso): ?>

                                    <tr>

                                        <td>
                                            <?= escapar($curso["id"]) ?>
                                        </td>

                                        <td>

                                            <?php if (!empty($curso["imagen"])): ?>

                                                <img
                                                    src="<?= escapar($curso["imagen"]) ?>"
                                                    alt="Portada del curso"
                                                    class="imagen-curso"
                                                >

                                            <?php else: ?>

                                                <span>Sin imagen</span>

                                            <?php endif; ?>

                                        </td>

                                        <td>
                                            <?= escapar($curso["titulo"]) ?>
                                        </td>

                                        <td>
                                            <?= escapar($curso["categoria"]) ?>
                                        </td>

                                        <td>
                                            <?= escapar($curso["instructor"]) ?>
                                        </td>

                                        <td>
                                            $<?= number_format(
                                                $curso["precio"],
                                                2
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= escapar($curso["duracion"]) ?>
                                        </td>

                                        <td class="acciones">

                                            <a
                                                href="admin_curso_editar.php?id=<?= escapar($curso["id"]) ?>"
                                                class="btn-editar"
                                            >
                                                Editar
                                            </a>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>

        <?php endif; ?>

    </main>

</body>
</html>
