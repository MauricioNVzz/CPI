<?php
session_start();

include "conexion.php";
require_once "controllers/MultimediaController.php";
require_once "controllers/NivelController.php";
require_once "controllers/ProgresoController.php";

$nivelController = new NivelController($conexion);

$multimediaController = new MultimediaController($conexion);

// Obtener y validar el ID del curso
$id = $_GET["id"] ?? "";

if (!filter_var($id, FILTER_VALIDATE_INT) || $id <= 0) {
    die("ID de curso no válido.");
}

// Consultar el curso y su maestro
$sql = "
    SELECT
        c.Curso_id,
        c.Curso_nombre,
        c.Curso_desc,
        c.Curso_precio,
        c.Curso_duracion,
        c.Curso_imagen,
        u.Usuario_nombre
    FROM Cursos c
    INNER JOIN Usuarios u
        ON c.Curso_id_maestro = u.Usuario_id
    WHERE c.Curso_id = ?
";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error al preparar la consulta del curso.");
}

$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    die("El curso no existe.");
}

$curso = $resultado->fetch_assoc();
$stmt->close();

// Comprobar si el usuario compró el curso
$cursoComprado = false;

if (isset($_SESSION["usuario_id"])) {
    $usuarioId = (int) $_SESSION["usuario_id"];

    $sqlCompra = "
        SELECT Venta_id
        FROM Ventas
        WHERE Venta_id_usuario = ?
          AND Venta_id_curso = ?
          AND Venta_estado = 'Completada'
    ";

    $stmtCompra = $conexion->prepare($sqlCompra);

    if (!$stmtCompra) {
        die("Error al comprobar la compra.");
    }

    $stmtCompra->bind_param("ii", $usuarioId, $id);
    $stmtCompra->execute();

    $resultadoCompra = $stmtCompra->get_result();

    if ($resultadoCompra->num_rows > 0) {
        $cursoComprado = true;
    }

    $stmtCompra->close();
}

// ==================================
// CONSULTAR AVANCE DEL ALUMNO
// ==================================

$contenidosCompletados = [];

$progresoController = new ProgresoController($conexion);

if (
    isset($_SESSION["usuario_id"]) &&
    isset($_SESSION["usuario_rol"]) &&
    (int)$_SESSION["usuario_rol"] === 3 &&
    $cursoComprado
) {
    $contenidosCompletados =
        $progresoController->obtenerPorCurso(
            (int)$_SESSION["usuario_id"],
            (int)$id
        );
}

// Obtener los niveles del curso
$niveles = $nivelController->obtenerPorCurso($id);

// Obtener la multimedia de cada nivel
$multimediaPorNivel = [];

foreach ($niveles as $nivel) {

    $multimediaPorNivel[$nivel["id"]] =
        $multimediaController->obtenerPorNivel($nivel["id"]);
        
}

// ==================================
// CALCULAR PORCENTAJE DE AVANCE
// ==================================

$totalContenidos = 0;
$contenidosRealizados = 0;

foreach ($multimediaPorNivel as $lecciones) {

    foreach ($lecciones as $leccion) {

        $totalContenidos++;

        if (
            in_array(
                (int)$leccion["id"],
                $contenidosCompletados,
                true
            )
        ) {
            $contenidosRealizados++;
        }
    }
}

// Calcular porcentaje
$porcentajeProgreso = 0;

if ($totalContenidos > 0) {

    $porcentajeProgreso = (int) round(
        ($contenidosRealizados / $totalContenidos) * 100
    );

}
// Mantener temporalmente la lista anterior de multimedia
$multimedia = $multimediaController->obtenerPorCurso($id);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo htmlspecialchars($curso["Curso_nombre"], ENT_QUOTES, "UTF-8"); ?>
    </title>

    <link rel="stylesheet" href="css/curso_ver.css">
</head>

<body>

    <div class="ventana-curso">

        <!-- BARRA SUPERIOR -->
        <div class="barra-superior">

            <h1>
                <?php echo htmlspecialchars($curso["Curso_nombre"], ENT_QUOTES, "UTF-8"); ?>
            </h1>

            <div class="foto-perfil">
                <img src="" alt="Foto de perfil">
            </div>

        </div>

        <!-- CONTENIDO DEL CURSO -->
        <div class="contenido">

            <!-- INFORMACIÓN DEL TUTOR -->
            <div class="informacion-tutor">

                <div class="datos-tutor">
                    <p class="tutor">
                        Tutor:
                        <a href="#">
                            <?php echo htmlspecialchars($curso["Usuario_nombre"], ENT_QUOTES, "UTF-8"); ?>
                        </a>
                    </p>
                </div>

                <!-- PERFIL DEL TUTOR Y COMPRA -->
                <div class="perfil-tutor">

                    <div class="foto-juan">
                        <img src="" alt="Foto de perfil del tutor">
                    </div>

                    <?php if ($cursoComprado): ?>

                        <p class="curso-adquirido">
                            Curso adquirido
                        </p>

                    <?php else: ?>

                        <a
                            href="curso_venta.php?id=<?php echo (int) $curso["Curso_id"]; ?>"
                            class="boton-adquirir"
                        >
                            Adquirir curso
                        </a>

                    <?php endif; ?>

                    <p class="precio">
                        $<?php echo number_format((float) $curso["Curso_precio"], 2); ?>
                    </p>

                </div>

            </div>

            <!-- DESCRIPCIÓN -->
            <p class="descripcion">
                <?php echo htmlspecialchars($curso["Curso_desc"], ENT_QUOTES, "UTF-8"); ?>
            </p>

            <!-- DURACIÓN -->
            <p class="duracion">
                Duración:
                <?php echo htmlspecialchars($curso["Curso_duracion"], ENT_QUOTES, "UTF-8"); ?>
            </p>

            <!-- PROGRESO DEL CURSO -->
<?php if ($cursoComprado && (int)($_SESSION["usuario_rol"] ?? 0) === 3): ?>

    <section class="seccion-progreso-curso">

        <h2>Mi progreso en el curso</h2>

        <div class="informacion-progreso">

            <span>
                <?php echo $contenidosRealizados; ?>
                de
                <?php echo $totalContenidos; ?>
                contenidos completados
            </span>

            <strong>
                <?php echo $porcentajeProgreso; ?>%
            </strong>

        </div>

        <div
            class="barra-progreso"
            role="progressbar"
            aria-valuenow="<?php echo $porcentajeProgreso; ?>"
            aria-valuemin="0"
            aria-valuemax="100"
        >

            <div
                class="barra-progreso-llenado"
                style="width: <?php echo $porcentajeProgreso; ?>%;"
            ></div>

        </div>

        <?php if ($totalContenidos === 0): ?>

            <p>Este curso todavía no tiene contenidos.</p>

        <?php elseif ($porcentajeProgreso === 100): ?>

            <p>¡Felicidades! Has completado todos los contenidos del curso.</p>

        <?php endif; ?>

    </section>

<?php endif; ?>

            

<!-- VALORACIONES DEL CURSO -->
<section class="seccion-valoraciones">

    <h2>Calificaciones del curso</h2>

    <div id="resumen-valoraciones">
        <p>Cargando calificaciones...</p>
    </div>

    <?php if (
        isset($_SESSION["usuario_rol"]) &&
        (int)$_SESSION["usuario_rol"] === 3 &&
        $cursoComprado
    ): ?>

        <form id="form-valoracion">

            <label for="puntuacion-valoracion">
                Califica este curso del 1 al 10:
            </label>

            <select id="puntuacion-valoracion" required>
                <option value="">Selecciona una calificación</option>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <option value="<?php echo $i; ?>">
                        <?php echo $i; ?>
                    </option>
                <?php endfor; ?>
            </select>

            <button type="submit" id="btn-valoracion">
                Enviar calificación
            </button>

            <p id="mensaje-valoracion" role="status"></p>

        </form>

    <?php elseif (
        isset($_SESSION["usuario_rol"]) &&
        (int)$_SESSION["usuario_rol"] === 3 &&
        !$cursoComprado
    ): ?>

        <p>Adquiere este curso para poder calificarlo.</p>

    <?php endif; ?>

</section>

            <!-- LISTA DE LECCIONES POR NIVELES -->
<div class="cuadro-lecciones">

    <?php if (count($niveles) > 0): ?>

        <?php foreach ($niveles as $nivel): ?>

            <section class="seccion-nivel">

                <h2>
                    <?php echo htmlspecialchars(
                        $nivel["nombre"],
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>
                </h2>

                <?php if (!empty($nivel["descripcion"])): ?>
                    <p class="descripcion-nivel">
                        <?php echo htmlspecialchars(
                            $nivel["descripcion"],
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>
                    </p>
                <?php endif; ?>

                <?php
                $leccionesNivel =
                    $multimediaPorNivel[$nivel["id"]] ?? [];
                ?>

                <?php if (count($leccionesNivel) > 0): ?>

                    <?php foreach ($leccionesNivel as $leccion): ?>

                        <?php
                        $puedeVer =
                            $cursoComprado || $leccion["gratuito"];

                        $tipo = strtolower($leccion["tipo"]);

                        $datosLeccion = htmlspecialchars(
                            json_encode([
                                "id" => (int)$leccion["id"],
                                "archivo" => $leccion["archivo"],
                                "tipo" => $tipo,
                                "titulo" => $leccion["titulo"]
                            ], JSON_UNESCAPED_UNICODE |
                               JSON_HEX_APOS |
                               JSON_HEX_QUOT |
                               JSON_HEX_TAG |
                               JSON_HEX_AMP),
                            ENT_QUOTES,
                            "UTF-8"
                        );
                        ?>

                        <?php if ($puedeVer): ?>

                            <button
                                class="leccion"
                                type="button"
                                onclick='abrirLeccion(<?php echo $datosLeccion; ?>)'
                            >

                                <div class="video">
                                    <?php if ($tipo === "imagen"): ?>
                                        <span>🖼️</span>
                                    <?php elseif ($tipo === "pdf"): ?>
                                        <span>📄</span>
                                    <?php else: ?>
                                        <span>▶</span>
                                    <?php endif; ?>
                                </div>

                                <div class="texto-leccion">
                                    <p>
                                        <?php echo htmlspecialchars(
                                            $leccion["titulo"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>
                                    </p>
                                </div>

                            </button>

                        <?php else: ?>

                            <button
                                class="leccion"
                                type="button"
                                disabled
                            >

                                <div class="video">
                                    <span>🔒</span>
                                </div>

                                <div class="texto-leccion">
                                    <p>
                                        <?php echo htmlspecialchars(
                                            $leccion["titulo"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>
                                    </p>
                                </div>

                            </button>

                        <?php endif; ?>

                    <?php endforeach; ?>

                <?php else: ?>

                    <p>Este nivel todavía no tiene contenidos.</p>

                <?php endif; ?>

            </section>

        <?php endforeach; ?>

    <?php else: ?>

        <p>Este curso todavía no tiene niveles disponibles.</p>

    <?php endif; ?>

</div>

        </div><!-- Fin de .contenido -->

        <!-- VISOR ÚNICO DE IMÁGENES -->
        <div
            id="visor-imagen"
            class="visor-imagen"
            style="display: none;"
        >

            <div class="visor-imagen-contenido">

                <button
                    type="button"
                    class="cerrar-visor"
                    onclick="cerrarLeccion()"
                >
                    Cerrar ✕
                </button>

                <h2 id="titulo-leccion"></h2>

                <div id="seccion-progreso" style="display: none;">

    <button
        type="button"
        id="btn-completar-leccion"
    >
        Marcar como completado
    </button>

    <p id="mensaje-progreso" role="status"></p>

</div>

                <img
                    id="imagen-leccion"
                    src=""
                    alt="Contenido del curso"
                >

                <video
    id="video-leccion"
    controls
    playsinline
    preload="metadata"
    style="display: none;"
>
    Tu navegador no puede reproducir este video.
</video>


<!-- Sección de comentarios -->
<section class="seccion-comentarios">
    <h3>Comentarios de la lección</h3>

    <div id="lista-comentarios">
        <p>Cargando comentarios...</p>
    </div>

    <form id="form-comentario">
        <label for="contenido-comentario">
            Escribe tu comentario
        </label>

        <textarea
            id="contenido-comentario"
            name="contenido"
            maxlength="2000"
            rows="3"
            placeholder="Comparte tu opinión sobre esta lección..."
            required
        ></textarea>

        <button type="submit" id="btn-comentar">
            Publicar comentario
        </button>

        <p id="mensaje-comentario" role="status"></p>
    </form>
</section>

            </div>

        </div><!-- Fin del visor -->

    </div><!-- Fin de .ventana-curso -->

    
<script>
const endpointComentarios = "/CPI/api/comentarios.php";

let multimediaActual = null;

const endpointProgreso = "/CPI/api/progreso.php";

const cursoIdProgreso = <?php echo (int)$curso["Curso_id"]; ?>;

const alumnoPuedeRegistrarProgreso = <?php echo (
    isset($_SESSION["usuario_rol"]) &&
    (int)$_SESSION["usuario_rol"] === 3 &&
    $cursoComprado
) ? "true" : "false"; ?>;

const contenidosCompletados = <?php echo json_encode(
    $contenidosCompletados
); ?>;

async function cargarComentarios(multimediaId) {
    const lista = document.getElementById("lista-comentarios");
    lista.textContent = "Cargando comentarios...";

    try {
        const respuesta = await fetch(
            `${endpointComentarios}?multimedia_id=${encodeURIComponent(multimediaId)}`
        );

        const datos = await respuesta.json();

        if (!respuesta.ok) {
            throw new Error(datos.error || "No se pudieron cargar los comentarios.");
        }

        // Evitar mostrar comentarios de una lección que ya se cerró
        // o se cambió mientras se realizaba la petición.
        if (String(multimediaActual) !== String(multimediaId)) {
            return;
        }

        lista.replaceChildren();

        if (!datos.comentarios || datos.comentarios.length === 0) {
            const mensaje = document.createElement("p");
            mensaje.textContent = "Todavía no hay comentarios. ¡Sé el primero!";
            lista.appendChild(mensaje);
            return;
        }

        
datos.comentarios.forEach(comentario => {
    const elemento = document.createElement("article");

    const eliminado = Number(comentario.Comentario_eliminado) === 1;

    elemento.className = eliminado
        ? "comentario comentario-eliminado"
        : "comentario";

    const nombre = document.createElement("strong");
    nombre.textContent = comentario.Usuario_nombre || "Usuario";

    const fecha = document.createElement("small");
    fecha.textContent = comentario.Comentario_fecha;

    const contenido = document.createElement("p");
    contenido.textContent = comentario.Comentario_contenido;

    elemento.append(nombre, fecha, contenido);

    if (eliminado) {
        const etiqueta = document.createElement("small");
        etiqueta.textContent = "Comentario eliminado por administración";
        etiqueta.className = "etiqueta-eliminado";

        elemento.appendChild(etiqueta);
    }

    lista.appendChild(elemento);
});


    } catch (error) {
        if (String(multimediaActual) !== String(multimediaId)) {
            return;
        }

        lista.textContent = error.message;
    }
}


// Mostrar el estado de progreso de la lección seleccionada
function actualizarEstadoProgreso(multimediaId) {

    const seccion = document.getElementById("seccion-progreso");
    const boton = document.getElementById("btn-completar-leccion");
    const mensaje = document.getElementById("mensaje-progreso");

    if (!alumnoPuedeRegistrarProgreso) {
        seccion.style.display = "none";
        return;
    }

    seccion.style.display = "block";
    mensaje.textContent = "";

    if (contenidosCompletados.includes(Number(multimediaId))) {
        boton.textContent = "✓ Contenido completado";
        boton.disabled = true;
    } else {
        boton.textContent = "Marcar como completado";
        boton.disabled = false;
    }
}


// Guardar el progreso del alumno
async function completarLeccion() {

    const boton = document.getElementById("btn-completar-leccion");
    const mensaje = document.getElementById("mensaje-progreso");

    if (!multimediaActual || !alumnoPuedeRegistrarProgreso) {
        mensaje.textContent = "No puedes registrar el avance de esta lección.";
        return;
    }

    boton.disabled = true;
    mensaje.textContent = "Guardando avance...";

    try {

        const respuesta = await fetch(endpointProgreso, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                curso_id: cursoIdProgreso,
                multimedia_id: multimediaActual
            })
        });

        const datos = await respuesta.json();

        if (!respuesta.ok || datos.error) {
            throw new Error(
                datos.mensaje || "No se pudo registrar el avance."
            );
        }

        if (!contenidosCompletados.includes(multimediaActual)) {
            contenidosCompletados.push(multimediaActual);
        }

        boton.textContent = "✓ Contenido completado";
        mensaje.textContent = datos.mensaje;

    } catch (error) {

        mensaje.textContent = error.message;
        boton.disabled = false;

    }
}


async function abrirLeccion(leccion) {
    const visor = document.getElementById("visor-imagen");
    const imagen = document.getElementById("imagen-leccion");
    const video = document.getElementById("video-leccion");
    const titulo = document.getElementById("titulo-leccion");

    titulo.textContent = leccion.titulo;

    // El ID debe venir de la lección seleccionada en curso_ver.php.
    multimediaActual = Number(leccion.id);
    actualizarEstadoProgreso(multimediaActual);

if (!Number.isInteger(multimediaActual) || multimediaActual <= 0) {
    multimediaActual = null;
    console.error("ID de lección inválido:", leccion);
    document.getElementById("lista-comentarios").textContent =
        "No se pudo identificar esta lección.";
    return;
}
    const lista = document.getElementById("lista-comentarios");
    lista.textContent = "Cargando comentarios...";

    if (leccion.tipo === "imagen") {
        video.pause();
        video.removeAttribute("src");
        video.load();
        video.style.display = "none";

        imagen.src = leccion.archivo;
        imagen.style.display = "block";

        visor.style.display = "flex";
        cargarComentarios(leccion.id);
        return;
    }

    if (leccion.tipo === "video") {
        imagen.removeAttribute("src");
        imagen.style.display = "none";

        video.src = leccion.archivo;
        video.style.display = "block";

        visor.style.display = "flex";
        video.load();

        cargarComentarios(leccion.id);
        return;
    }

    multimediaActual = null;
    alert("Este tipo de contenido todavía no tiene visor.");
}

async function publicarComentario(evento) {
    evento.preventDefault();

    const formulario = document.getElementById("form-comentario");
    const campo = document.getElementById("contenido-comentario");
    const boton = document.getElementById("btn-comentar");
    const mensaje = document.getElementById("mensaje-comentario");

    if (!multimediaActual) {
        mensaje.textContent = "Primero abre una lección.";
        return;
    }

    const contenido = campo.value.trim();

    if (!contenido) {
        mensaje.textContent = "Escribe un comentario antes de publicarlo.";
        return;
    }

    boton.disabled = true;
    mensaje.textContent = "Publicando comentario...";

    try {
        const respuesta = await fetch(endpointComentarios, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                multimedia_id: multimediaActual,
                contenido: contenido
            })
        });

        const datos = await respuesta.json();

        if (!respuesta.ok) {
            throw new Error(datos.error || "No se pudo publicar el comentario.");
        }

        campo.value = "";
        mensaje.textContent = datos.mensaje || "Comentario publicado.";

        await cargarComentarios(multimediaActual);

    } catch (error) {
        mensaje.textContent = error.message;
    } finally {
        boton.disabled = false;
    }
}

function cerrarLeccion() {
    const visor = document.getElementById("visor-imagen");
    const imagen = document.getElementById("imagen-leccion");
    const video = document.getElementById("video-leccion");

    multimediaActual = null;

    video.pause();
    video.removeAttribute("src");
    video.load();

    imagen.removeAttribute("src");

    video.style.display = "none";
    imagen.style.display = "none";
    visor.style.display = "none";
}

document
    .getElementById("form-comentario")
    .addEventListener("submit", publicarComentario);

document
    .getElementById("btn-completar-leccion")
    .addEventListener("click", completarLeccion);

    

const endpointValoraciones = "/CPI/api/calificaciones.php";

const cursoIdValoracion = <?php echo (int)$curso["Curso_id"]; ?>;

async function cargarValoraciones() {

    const resumen = document.getElementById("resumen-valoraciones");

    try {

        const respuesta = await fetch(
            `${endpointValoraciones}?curso_id=${cursoIdValoracion}`
        );

        const datos = await respuesta.json();

        if (!respuesta.ok || datos.error) {
            throw new Error(
                datos.mensaje || "No se pudieron cargar las calificaciones."
            );
        }

        resumen.replaceChildren();

        const puntuacion = document.createElement("p");

        puntuacion.textContent =
            `Promedio: ${Number(datos.promedio).toFixed(2)} / 10`;

        const total = document.createElement("p");

        total.textContent =
            `Total de calificaciones: ${datos.total_calificaciones}`;

        resumen.append(puntuacion, total);

        // Mostrar la calificación actual del alumno
        if (datos.mi_calificacion !== null) {

            const select = document.getElementById("puntuacion-valoracion");
            const boton = document.getElementById("btn-valoracion");

            if (select && boton) {
                select.value = datos.mi_calificacion;
                boton.textContent = "Actualizar calificación";
            }
        }

    } catch (error) {

        resumen.textContent = error.message;

    }
}


async function publicarValoracion(evento) {

    evento.preventDefault();

    const select = document.getElementById("puntuacion-valoracion");
    const boton = document.getElementById("btn-valoracion");
    const mensaje = document.getElementById("mensaje-valoracion");

    const calificacion = Number(select.value);

    if (!Number.isInteger(calificacion) ||
        calificacion < 1 ||
        calificacion > 10) {

        mensaje.textContent = "Selecciona una calificación válida del 1 al 10.";
        return;
    }

    boton.disabled = true;
    mensaje.textContent = "Guardando calificación...";

    try {

        const respuesta = await fetch(endpointValoraciones, {

            method: "POST",

            headers: {
                "Content-Type": "application/json"
            },

            body: JSON.stringify({
                curso_id: cursoIdValoracion,
                calificacion: calificacion
            })

        });

        const datos = await respuesta.json();

        if (!respuesta.ok || datos.error) {
            throw new Error(
                datos.mensaje || "No se pudo guardar la calificación."
            );
        }

        mensaje.textContent =
            datos.mensaje || "Calificación guardada correctamente.";

        await cargarValoraciones();

        boton.textContent = "Actualizar calificación";

    } catch (error) {

        mensaje.textContent = error.message;

    } finally {

        boton.disabled = false;

    }
}


// Inicializar calificaciones
document.addEventListener("DOMContentLoaded", () => {

    cargarValoraciones();

    const formulario = document.getElementById("form-valoracion");

    if (formulario) {
        formulario.addEventListener("submit", publicarValoracion);
    }

});

</script>

</body>
</html>