
<?php

require_once __DIR__ . "/middleware/auth.php";

verificarAcceso([3]);

require_once __DIR__ . "/conexion.php";

// ==========================================
// FILTROS
// ==========================================

$desde = $_GET["desde"] ?? "";
$hasta = $_GET["hasta"] ?? "";

$error = "";

if (
    ($desde !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) ||
    ($hasta !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) ||
    ($desde !== "" && $hasta !== "" && $desde > $hasta)
) {
    $error = "El rango de fechas no es válido.";
}

// ==========================================
// CONSULTAR KARDEX
// ==========================================

$sql = "
    SELECT
        v.Venta_id,
        c.Curso_nombre,
        m.Usuario_nombre AS Maestro,
        v.Venta_fecha,
        v.Venta_costo,
        v.Venta_estado,
        v.Venta_moneda
    FROM Ventas v
    INNER JOIN Cursos c
        ON v.Venta_id_curso = c.Curso_id
    INNER JOIN Usuarios m
        ON c.Curso_id_maestro = m.Usuario_id
    WHERE v.Venta_id_usuario = ?
";

$tipos = "i";
$parametros = [(int)$_SESSION["usuario_id"]];

if ($error === "" && $desde !== "") {
    $sql .= " AND v.Venta_fecha >= ?";
    $tipos .= "s";
    $parametros[] = $desde;
}

if ($error === "" && $hasta !== "") {
    $sql .= " AND v.Venta_fecha < DATE_ADD(?, INTERVAL 1 DAY)";
    $tipos .= "s";
    $parametros[] = $hasta;
}

$sql .= " ORDER BY v.Venta_fecha DESC";

$stmt = $conexion->prepare($sql);
$stmt->bind_param($tipos, ...$parametros);
$stmt->execute();

$resultado = $stmt->get_result();

$compras = [];
$totalGastado = 0;

while ($fila = $resultado->fetch_assoc()) {
    $compras[] = $fila;

    if ($fila["Venta_estado"] === "Completada") {
        $totalGastado += (float)$fila["Venta_costo"];
    }
}

$totalCompras = count($compras);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mi Kardex - CPI</title>

    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/kardex.css">

</head>

<body>

    <?php require_once __DIR__ . "/includes/navbar.php"; ?>

    <main class="contenedor-kardex">

        <section class="encabezado-kardex">

            <h1>Mi Kardex</h1>

            <p>
                Consulta tu historial de compras y cursos adquiridos.
            </p>

        </section>

        <?php if ($error !== ""): ?>

            <div class="mensaje-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <section class="resumen-kardex">

            <div class="tarjeta-resumen">

                <h3>Total de compras</h3>

                <p><?php echo $totalCompras; ?></p>

            </div>

            <div class="tarjeta-resumen">

                <h3>Total gastado</h3>

                <p>
                    $<?php echo number_format($totalGastado, 2); ?> MXN
                </p>

            </div>

        </section>

        <section class="filtros-kardex">

            <h2>Filtrar historial</h2>

            <form method="GET" action="kardex.php">

                <div class="grupo-filtro">

                    <label for="desde">Fecha inicial:</label>

                    <input
                        type="date"
                        id="desde"
                        name="desde"
                        value="<?php echo htmlspecialchars($desde); ?>"
                    >

                </div>

                <div class="grupo-filtro">

                    <label for="hasta">Fecha final:</label>

                    <input
                        type="date"
                        id="hasta"
                        name="hasta"
                        value="<?php echo htmlspecialchars($hasta); ?>"
                    >

                </div>

                <div class="acciones-filtros">

                    <button type="submit">
                        Aplicar filtros
                    </button>

                    <a href="kardex.php">
                        Limpiar filtros
                    </a>

                </div>

            </form>

        </section>

        <section class="tabla-kardex">

            <h2>Historial de compras</h2>

            <div class="tabla-contenedor">

                <table>

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Curso</th>
                            <th>Maestro</th>
                            <th>Fecha de compra</th>
                            <th>Costo</th>
                            <th>Estado</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if ($totalCompras > 0): ?>

                            <?php foreach ($compras as $compra): ?>

                                <tr>

                                    <td>
                                        <?php echo (int)$compra["Venta_id"]; ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($compra["Curso_nombre"]); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($compra["Maestro"]); ?>
                                    </td>

                                    <td>
                                        <?php echo date(
                                            "d/m/Y",
                                            strtotime($compra["Venta_fecha"])
                                        ); ?>
                                    </td>

                                    <td>
                                        $<?php echo number_format(
                                            (float)$compra["Venta_costo"],
                                            2
                                        ); ?>
                                        <?php echo htmlspecialchars($compra["Venta_moneda"]); ?>
                                    </td>

                                    <td>
                                        <span class="estado <?php echo strtolower(htmlspecialchars($compra["Venta_estado"])); ?>">
                                            <?php echo htmlspecialchars($compra["Venta_estado"]); ?>
                                        </span>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="6">
                                    No se encontraron compras en este periodo.
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

<?php
$resultado->free();
$stmt->close();
?>