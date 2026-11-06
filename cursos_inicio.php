<?php

require_once __DIR__ . "/middleware/auth.php";

verificarAcceso([1, 2, 3]);

require_once __DIR__ . "/conexion.php";

// ==========================================
// RECUPERAR FILTROS
// ==========================================

$categoriaFiltro = (int)($_GET["categoria"] ?? 0);
$tituloFiltro = trim($_GET["titulo"] ?? "");
$instructorFiltro = (int)($_GET["instructor"] ?? 0);
$fechaInicio = trim($_GET["fecha_inicio"] ?? "");
$fechaFin = trim($_GET["fecha_fin"] ?? "");

$errorBusqueda = "";

// ==========================================
// VALIDAR FECHAS
// ==========================================

function fechaValida($fecha)
{
    if ($fecha === "") {
        return true;
    }

    $formato = DateTime::createFromFormat("Y-m-d", $fecha);

    return $formato &&
        $formato->format("Y-m-d") === $fecha;
}

if (!fechaValida($fechaInicio) || !fechaValida($fechaFin)) {

    $errorBusqueda = "Las fechas ingresadas no son válidas.";

} elseif (
    $fechaInicio !== "" &&
    $fechaFin !== "" &&
    $fechaInicio > $fechaFin
) {

    $errorBusqueda = "La fecha inicial no puede ser posterior a la fecha final.";

}

// ==========================================
// OBTENER CATEGORÍAS
// ==========================================

$consultaCategorias = "
    SELECT Categoria_id, Categoria_nombre
    FROM Categorias
    ORDER BY Categoria_nombre ASC
";

$categorias = $conexion->query($consultaCategorias);

// ==========================================
// OBTENER INSTRUCTORES CON CURSOS ACTIVOS
// ==========================================

$consultaInstructores = "
    SELECT DISTINCT
        u.Usuario_id,
        u.Usuario_nombre
    FROM Usuarios u
    INNER JOIN Cursos c
        ON c.Curso_id_maestro = u.Usuario_id
    WHERE c.Curso_estado = 1
    ORDER BY u.Usuario_nombre ASC
";

$instructores = $conexion->query($consultaInstructores);

// ==========================================
// BUSCAR CURSOS CON FILTROS
// ==========================================

$sql = "
    SELECT
        c.Curso_id,
        c.Curso_nombre,
        c.Curso_desc,
        c.Curso_precio,
        c.Curso_imagen,
        u.Usuario_nombre
    FROM Cursos c
    INNER JOIN Usuarios u
        ON c.Curso_id_maestro = u.Usuario_id
    WHERE c.Curso_estado = 1

    AND (? = 0 OR c.Curso_id_cat = ?)

    AND (? = '' OR c.Curso_nombre LIKE ?)

    AND (? = 0 OR u.Usuario_id = ?)

    AND (? = '' OR DATE(c.Curso_fecha) >= ?)

    AND (? = '' OR DATE(c.Curso_fecha) <= ?)

    ORDER BY c.Curso_fecha DESC
";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error al preparar la búsqueda de cursos.");
}

$tituloBusqueda = "%" . $tituloFiltro . "%";

$stmt->bind_param(
    "iissiissss",
    $categoriaFiltro,
    $categoriaFiltro,
    $tituloFiltro,
    $tituloBusqueda,
    $instructorFiltro,
    $instructorFiltro,
    $fechaInicio,
    $fechaInicio,
    $fechaFin,
    $fechaFin
);

if ($errorBusqueda === "") {

    $stmt->execute();

    $resultado = $stmt->get_result();

} else {

    $resultado = false;

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

    <title>Inicio - CPI Cursos</title>

    <link rel="stylesheet" href="css/cursos_inicio.css">
    <link rel="stylesheet" href="css/navbar.css">

</head>

<body>


    <!-- ==========================================
         BARRA DE NAVEGACIÓN
         ========================================== -->

    <?php require_once __DIR__ . "/includes/navbar.php"; ?>

    <!-- ==========================================
         PRESENTACIÓN
         ========================================== -->

    <main>

        <section class="presentacion">

            <div class="presentacion-texto">

                <h1>
                    Aprende algo nuevo
                </h1>

                <p>
                    Encuentra cursos, desarrolla nuevas
                    habilidades y aprende a tu propio ritmo.
                </p>

                <a
                    href="#cursos"
                    class="boton-principal"
                >
                    Explorar cursos
                </a>

            </div>


            <div class="presentacion-imagen">

                <img
                    src="images/imagen.jpg"
                    alt="Cursos CPI"
                >

            </div>

        </section>


        <!-- ==========================================
             CURSOS
             ========================================== -->

        <section
            id="cursos"
            class="seccion-cursos"
        >

            <h2>
                Cursos disponibles
            </h2>

            <p class="subtitulo">

                Aprende con nuestros cursos y
                comienza a desarrollar nuevas habilidades.

            </p>

            <!-- ==========================================
     BUSCADOR Y FILTROS
     ========================================== -->

<form method="GET" action="cursos_inicio.php" class="formulario-busqueda">

    <div class="campo-filtro">

        <label for="titulo">Título del curso</label>

        <input
            type="text"
            id="titulo"
            name="titulo"
            placeholder="Buscar curso..."
            value="<?php echo htmlspecialchars($tituloFiltro, ENT_QUOTES, 'UTF-8'); ?>"
        >

    </div>


    <div class="campo-filtro">

        <label for="categoria">Categoría</label>

        <select name="categoria" id="categoria">

            <option value="0">Todas las categorías</option>

            <?php while ($categoria = $categorias->fetch_assoc()): ?>

                <option
                    value="<?php echo (int)$categoria["Categoria_id"]; ?>"
                    <?php echo $categoriaFiltro === (int)$categoria["Categoria_id"] ? "selected" : ""; ?>
                >

                    <?php echo htmlspecialchars($categoria["Categoria_nombre"], ENT_QUOTES, 'UTF-8'); ?>

                </option>

            <?php endwhile; ?>

        </select>

    </div>


    <div class="campo-filtro">

        <label for="instructor">Publicado por</label>

        <select name="instructor" id="instructor">

            <option value="0">Todos los instructores</option>

            <?php while ($instructor = $instructores->fetch_assoc()): ?>

                <option
                    value="<?php echo (int)$instructor["Usuario_id"]; ?>"
                    <?php echo $instructorFiltro === (int)$instructor["Usuario_id"] ? "selected" : ""; ?>
                >

                    <?php echo htmlspecialchars($instructor["Usuario_nombre"], ENT_QUOTES, 'UTF-8'); ?>

                </option>

            <?php endwhile; ?>

        </select>

    </div>


    <div class="campo-filtro">

        <label for="fecha_inicio">Fecha inicial</label>

        <input
            type="date"
            id="fecha_inicio"
            name="fecha_inicio"
            value="<?php echo htmlspecialchars($fechaInicio, ENT_QUOTES, 'UTF-8'); ?>"
        >

    </div>


    <div class="campo-filtro">

        <label for="fecha_fin">Fecha final</label>

        <input
            type="date"
            id="fecha_fin"
            name="fecha_fin"
            value="<?php echo htmlspecialchars($fechaFin, ENT_QUOTES, 'UTF-8'); ?>"
        >

    </div>


    <div class="botones-busqueda">

        <button type="submit" class="boton-buscar">
            Buscar cursos
        </button>

        <a href="cursos_inicio.php" class="boton-limpiar">
            Limpiar filtros
        </a>

    </div>

</form>


<?php if ($errorBusqueda !== ""): ?>

    <p class="mensaje-error">
        <?php echo htmlspecialchars($errorBusqueda, ENT_QUOTES, 'UTF-8'); ?>
    </p>

<?php endif; ?>

            <div class="contenedor-cursos">


                <?php if ($resultado->num_rows > 0): ?>


                    <?php while ($curso = $resultado->fetch_assoc()): ?>


                        <article class="tarjeta-curso">


                            <!-- IMAGEN -->

                            <div class="imagen-curso">

                                <?php if (
                                    !empty($curso["Curso_imagen"])
                                ): ?>

                                    <img
                                        src="<?php
                                        echo htmlspecialchars(
                                            $curso["Curso_imagen"]
                                        );
                                        ?>"
                                        alt="<?php
                                        echo htmlspecialchars(
                                            $curso["Curso_nombre"]
                                        );
                                        ?>"
                                    >

                                <?php else: ?>

                                    <img
                                        src="images/imagen.jpg"
                                        alt="Curso"
                                    >

                                <?php endif; ?>

                            </div>


                            <!-- INFORMACIÓN -->

                            <div class="informacion-curso">

                                <h3>

                                    <?php
                                    echo htmlspecialchars(
                                        $curso["Curso_nombre"]
                                    );
                                    ?>

                                </h3>


                                <p>

                                    <?php
                                    echo htmlspecialchars(
                                        $curso["Curso_desc"]
                                    );
                                    ?>

                                </p>


                                <p>

                                    Instructor:

                                    <?php
                                    echo htmlspecialchars(
                                        $curso["Usuario_nombre"]
                                    );
                                    ?>

                                </p>


                                <span class="precio">

                                    $
                                    <?php
                                    echo number_format(
                                        $curso["Curso_precio"],
                                        2
                                    );
                                    ?>
                                    MXN

                                </span>


                                <a
                                    href="curso_ver.php?id=<?php
                                    echo $curso["Curso_id"];
                                    ?>"
                                    class="boton-curso"
                                >
                                    Ver curso
                                </a>

                            </div>


                        </article>


                    <?php endwhile; ?>


                <?php else: ?>


                    <div class="sin-cursos">

                        <h3>
                            No hay cursos disponibles
                        </h3>

                        <p>
                            Actualmente no hay cursos
                            registrados en la plataforma.
                        </p>

                    </div>


                <?php endif; ?>


            </div>

        </section>


        <!-- ==========================================
             INFORMACIÓN
             ========================================== -->

        <section class="informacion">

            <h2>
                Aprende a tu propio ritmo
            </h2>

            <p>

                Explora nuestros cursos, encuentra
                el contenido que necesitas y desarrolla
                nuevas habilidades desde cualquier lugar.

            </p>

        </section>

    </main>


    <!-- ==========================================
         FOOTER
         ========================================== -->

    <footer>

        <p>
            © 2026 CPI Cursos
        </p>

    </footer>


</body>

</html>