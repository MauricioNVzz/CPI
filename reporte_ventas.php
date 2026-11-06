<?php

require_once __DIR__ . "/middleware/auth.php";

verificarAcceso([1, 2]);

require_once __DIR__ . "/conexion.php";

// ==========================================
// FILTROS
// ==========================================

$desde = $_GET["desde"] ?? "";
$hasta = $_GET["hasta"] ?? "";
$vista = $_GET["vista"] ?? "detalle";

$error = "";

if (!in_array($vista, ["detalle", "resumen"], true)) {
    $vista = "detalle";
    $error = "La vista seleccionada no es válida.";
}

if (
    ($desde !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) ||
    ($hasta !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) ||
    ($desde !== "" && $hasta !== "" && $desde > $hasta)
) {
    $error = "El rango de fechas no es válido.";
}

// ==========================================
// CONSULTAR REPORTE
// ==========================================

$usuarioId = (int)$_SESSION["usuario_id"];
$rol = (int)$_SESSION["usuario_rol"];

$tipos = "";
$parametros = [];

if ($vista === "detalle") {

    $sql = "
        SELECT
            v.Venta_id,
            a.Usuario_nombre AS Alumno,
            c.Curso_nombre,
            v.Venta_costo,
            v.Venta_fecha,
            v.Venta_estado,
            v.Venta_moneda
        FROM Ventas v
        INNER JOIN Cursos c
            ON v.Venta_id_curso = c.Curso_id
        INNER JOIN Usuarios a
            ON v.Venta_id_usuario = a.Usuario_id
        WHERE 1 = 1
    ";

    if ($rol === 2) {
        $sql .= " AND c.Curso_id_maestro = ?";
        $tipos .= "i";
        $parametros[] = $usuarioId;
    }

} else {

    $sql = "
        SELECT
            c.Curso_id,
            c.Curso_nombre,
            COUNT(v.Venta_id) AS Total_ventas,
            COALESCE(SUM(
                CASE
                    WHEN v.Venta_estado = 'Completada'
                    THEN v.Venta_costo
                    ELSE 0
                END
            ), 0) AS Ingresos,
            'MXN' AS Venta_moneda
        FROM Cursos c
        LEFT JOIN Ventas v
            ON c.Curso_id = v.Venta_id_curso
    ";

    // Los filtros de fechas se aplican dentro del LEFT JOIN.
    if ($desde !== "") {
        $sql .= " AND v.Venta_fecha >= ?";
        $tipos .= "s";
        $parametros[] = $desde;
    }

    if ($hasta !== "") {
        $sql .= " AND v.Venta_fecha < DATE_ADD(?, INTERVAL 1 DAY)";
        $tipos .= "s";
        $parametros[] = $hasta;
    }

    if ($rol === 2) {
        $sql .= " WHERE c.Curso_id_maestro = ?";
        $tipos .= "i";
        $parametros[] = $usuarioId;
    } else {
        $sql .= " WHERE 1 = 1";
    }

}

// ==========================================
// FILTROS DE FECHAS PARA DETALLE
// ==========================================

if ($vista === "detalle" && $error === "" && $desde !== "") {

    $sql .= " AND v.Venta_fecha >= ?";
    $tipos .= "s";
    $parametros[] = $desde;
}

if ($vista === "detalle" && $error === "" && $hasta !== "") {

    $sql .= " AND v.Venta_fecha < DATE_ADD(?, INTERVAL 1 DAY)";
    $tipos .= "s";
    $parametros[] = $hasta;
}
// ==========================================
// ORDENAR RESULTADOS
// ==========================================

if ($vista === "detalle") {

    $sql .= " ORDER BY v.Venta_fecha DESC";

} else {

    $sql .= "
        GROUP BY c.Curso_id, c.Curso_nombre
        ORDER BY Ingresos DESC
    ";

}

// ==========================================
// EJECUTAR CONSULTA
// ==========================================

$datos = [];
$totalRegistros = 0;
$totalIngresos = 0;

if ($error === "") {

    $stmt = $conexion->prepare($sql);

    if (!empty($parametros)) {
        $stmt->bind_param($tipos, ...$parametros);
    }

    $stmt->execute();

    $resultado = $stmt->get_result();

    while ($fila = $resultado->fetch_assoc()) {

        $datos[] = $fila;

        if ($vista === "detalle") {

            if ($fila["Venta_estado"] === "Completada") {
                $totalIngresos += (float)$fila["Venta_costo"];
            }

        } else {

            $totalIngresos += (float)$fila["Ingresos"];

        }
    }

    $totalRegistros = count($datos);

    $resultado->free();
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reporte de ventas - CPI</title>

    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/reporte_ventas.css">

</head>

<body>

    <?php require_once __DIR__ . "/includes/navbar.php"; ?>

    <main class="contenedor-reporte">

        <section class="encabezado-reporte">

            <h1>Reporte de ventas</h1>

            <p>
                Consulta las ventas y los ingresos generados por los cursos.
            </p>

        </section>

        <?php if ($error !== ""): ?>

            <div class="mensaje-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <section class="resumen-reporte">

            <div class="tarjeta-resumen">

                <h3>
                    <?php echo $vista === "detalle" ? "Ventas encontradas" : "Cursos encontrados"; ?>
                </h3>

                <p><?php echo $totalRegistros; ?></p>

            </div>

            <div class="tarjeta-resumen">

                <h3>Ingresos completados</h3>

                <p>
                    $<?php echo number_format($totalIngresos, 2); ?> MXN
                </p>

            </div>

        </section>

        <section class="filtros-reporte">

            <h2>Filtros del reporte</h2>

            <form method="GET" action="reporte_ventas.php">

                <div class="grupo-filtro">

                    <label for="vista">Tipo de reporte:</label>

                    <select name="vista" id="vista">

                        <option value="detalle" <?php echo $vista === "detalle" ? "selected" : ""; ?>>
                            Detalle de ventas
                        </option>

                        <option value="resumen" <?php echo $vista === "resumen" ? "selected" : ""; ?>>
                            Resumen por curso
                        </option>

                    </select>

                </div>

                <div class="grupo-filtro">

                    <label for="desde">Fecha inicial:</label>

                    <input
                        type="date"
                        name="desde"
                        id="desde"
                        value="<?php echo htmlspecialchars($desde); ?>"
                    >

                </div>

                <div class="grupo-filtro">

                    <label for="hasta">Fecha final:</label>

                    <input
                        type="date"
                        name="hasta"
                        id="hasta"
                        value="<?php echo htmlspecialchars($hasta); ?>"
                    >

                </div>

                <div class="acciones-filtros">

                    <button type="submit">
                        Aplicar filtros
                    </button>

                    <a href="reporte_ventas.php">
                        Limpiar filtros
                    </a>

                </div>

            </form>

        </section>

        <section class="tabla-reporte">

            <h2>
                <?php echo $vista === "detalle" ? "Detalle de ventas" : "Resumen por curso"; ?>
            </h2>

            <div class="tabla-contenedor">

                <table>

                    <thead>

                        <?php if ($vista === "detalle"): ?>

                            <tr>
                                <th>ID</th>
                                <th>Alumno</th>
                                <th>Curso</th>
                                <th>Fecha</th>
                                <th>Costo</th>
                                <th>Estado</th>
                            </tr>

                        <?php else: ?>

                            <tr>
                                <th>ID del curso</th>
                                <th>Curso</th>
                                <th>Total de ventas</th>
                                <th>Ingresos</th>
                            </tr>

                        <?php endif; ?>

                    </thead>

                    <tbody>

                        <?php if ($totalRegistros > 0): ?>

                            <?php foreach ($datos as $fila): ?>

                                <tr>

                                    <?php if ($vista === "detalle"): ?>

                                        <td><?php echo (int)$fila["Venta_id"]; ?></td>

                                        <td>
                                            <?php echo htmlspecialchars($fila["Alumno"]); ?>
                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars($fila["Curso_nombre"]); ?>
                                        </td>

                                        <td>
                                            <?php echo date("d/m/Y", strtotime($fila["Venta_fecha"])); ?>
                                        </td>

                                        <td>
                                            $<?php echo number_format((float)$fila["Venta_costo"], 2); ?>
                                            <?php echo htmlspecialchars($fila["Venta_moneda"]); ?>
                                        </td>

                                        <td>
                                            <span class="estado <?php echo strtolower(htmlspecialchars($fila["Venta_estado"])); ?>">
                                                <?php echo htmlspecialchars($fila["Venta_estado"]); ?>
                                            </span>
                                        </td>

                                    <?php else: ?>

                                        <td><?php echo (int)$fila["Curso_id"]; ?></td>

                                        <td>
                                            <?php echo htmlspecialchars($fila["Curso_nombre"]); ?>
                                        </td>

                                        <td>
                                            <?php echo (int)$fila["Total_ventas"]; ?>
                                        </td>

                                        <td>
                                            $<?php echo number_format((float)$fila["Ingresos"], 2); ?>
                                            MXN
                                        </td>

                                    <?php endif; ?>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="<?php echo $vista === "detalle" ? 6 : 4; ?>">
                                    No se encontraron resultados.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</body>

</html>