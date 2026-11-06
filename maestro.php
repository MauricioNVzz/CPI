<?php

require_once __DIR__ . "/middleware/auth.php";

verificarAcceso([2]);
// Generar token CSRF si todavía no existe
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
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

// ==========================================
// OBTENER DATOS DEL MAESTRO
// ==========================================

$maestroId = (int)$_SESSION["usuario_id"];
$nombreMaestro = $_SESSION["usuario_nombre"] ?? "Maestro";

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
    name="csrf-token"
    content="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>"
>

    <title>Panel del maestro</title>

    <link rel="stylesheet" href="css/maestro.css">
    <link rel="stylesheet" href="css/navbar.css">

</head>

<body>

    <!-- ==================================
         BARRA SUPERIOR
         ================================== -->

    <?php require_once __DIR__ . "/includes/navbar.php"; ?>

    <!-- ==================================
         CONTENIDO
         ================================== -->

    <main class="contenedor">

        <section class="encabezado">

            <h1>
                Panel del maestro
            </h1>

            <p>
                Bienvenido,
                <?= htmlspecialchars($nombreMaestro, ENT_QUOTES, 'UTF-8') ?>.
            </p>

            <p>
                Aquí puedes administrar tus cursos
                y su contenido.
            </p>

        </section>

        <!-- ==================================
             CURSOS
             ================================== -->

        <section class="seccion-cursos">

            <!-- ==================================
                 FORMULARIO PARA CREAR CURSO
                 ================================== -->

            <div class="formulario-curso">

                <h3>Crear nuevo curso</h3>

                <p
                    id="mensaje-curso"
                    aria-live="polite"
                ></p>

                <form
                    id="formulario-curso"
                    enctype="multipart/form-data"
                >

                    <div class="campo">

                        <label for="titulo">
                            Nombre del curso:
                        </label>

                        <input
                            type="text"
                            id="titulo"
                            name="titulo"
                            maxlength="150"
                            required
                        >

                    </div>

                    <div class="campo">

                        <label for="descripcion">
                            Descripción:
                        </label>

                        <textarea
                            id="descripcion"
                            name="descripcion"
                            rows="4"
                            required
                        ></textarea>

                    </div>

                    <div class="campo">

                        <label for="categoria">
                            Categoría:
                        </label>

                        <select
                            id="categoria"
                            name="categoria"
                            required
                        >

                            <option value="">
                                Cargando categorías...
                            </option>

                        </select>

                    </div>

                    <div class="campo">

                        <label for="precio">
                            Precio:
                        </label>

                        <input
                            type="number"
                            id="precio"
                            name="precio"
                            min="0"
                            step="0.01"
                            required
                        >

                    </div>

                    <div class="campo">

                        <label for="duracion">
                            Duración:
                        </label>

                        <input
                            type="text"
                            id="duracion"
                            name="duracion"
                            placeholder="Ej. 20 horas"
                            maxlength="100"
                            required
                        >

                    </div>

                    <div class="campo">

                        <label for="imagen">
                            Imagen de portada:
                        </label>

                        <input
                            type="file"
                            id="imagen"
                            name="imagen"
                            accept=".jpg,.jpeg,.png,.webp"
                        >

                        <small>
                            Formatos permitidos: JPG, PNG y WEBP.
                            Máximo 5 MB.
                        </small>

                    </div>

                    <button
                        type="submit"
                        id="boton-crear"
                    >
                        Crear curso
                    </button>

                </form>

            </div>

            <!-- ==================================
                 LISTADO DE CURSOS
                 ================================== -->

            <h2>
                Mis cursos
            </h2>

            <div
                id="lista-cursos"
                class="contenedor-cursos"
                data-instructor="<?= htmlspecialchars(
                    (string)$maestroId,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

                <p>
                    Cargando tus cursos...
                </p>

            </div>

        </section>

    </main>

    <!-- ==================================
         FOOTER
         ================================== -->

    <footer>

        <p>
            © 2026 CPI Cursos
        </p>

    </footer>

    <!-- ==================================
         JAVASCRIPT Y API
         ================================== -->

    <script>

        document.addEventListener("DOMContentLoaded", () => {

            const API = "api/api.php";

            const CSRF_TOKEN = document.querySelector(
    'meta[name="csrf-token"]'
).content;

            const formulario = document.getElementById("formulario-curso");

            const mensaje = document.getElementById("mensaje-curso");

            const botonCrear = document.getElementById("boton-crear");

            const selectCategoria = document.getElementById("categoria");

            const contenedorCursos = document.getElementById("lista-cursos");

            const instructor = contenedorCursos.dataset.instructor;


            // ==========================================
            // MOSTRAR MENSAJES
            // ==========================================

            function mostrarMensaje(texto, tipo) {

                mensaje.textContent = texto;

                mensaje.className = tipo;

            }


            // ==========================================
            // CARGAR CATEGORÍAS MEDIANTE GET
            // ==========================================

            async function cargarCategorias() {

                try {

                    const respuesta = await fetch(
                        `${API}?categorias=1`,
                        {
                            method: "GET",
                            credentials: "same-origin"
                        }
                    );

                    const datos = await respuesta.json();

                    if (!respuesta.ok || datos.error) {
                        throw new Error(
                            datos.mensaje || "No fue posible cargar las categorías."
                        );
                    }

                    selectCategoria.innerHTML = "";

                    const opcionInicial = document.createElement("option");

                    opcionInicial.value = "";

                    opcionInicial.textContent = "Selecciona una categoría";

                    selectCategoria.appendChild(opcionInicial);

                    datos.categorias.forEach(categoria => {

                        const opcion = document.createElement("option");

                        opcion.value = categoria.id;

                        opcion.textContent = categoria.nombre;

                        selectCategoria.appendChild(opcion);

                    });

                } catch (error) {

                    console.error("Error al cargar categorías:", error);

                    selectCategoria.innerHTML = "";

                    const opcion = document.createElement("option");

                    opcion.value = "";

                    opcion.textContent = "No fue posible cargar las categorías";

                    selectCategoria.appendChild(opcion);

                    mostrarMensaje(
                        "No fue posible cargar las categorías.",
                        "mensaje-error"
                    );

                }

            }


            // ==========================================
            // CONSULTAR CURSOS MEDIANTE GET
            // ==========================================

            async function cargarCursos() {

                try {

                    const respuesta = await fetch(
                        `${API}?instructor=${encodeURIComponent(instructor)}`,
                        {
                            method: "GET",
                            credentials: "same-origin"
                        }
                    );

                    const datos = await respuesta.json();
                    console.log("Cursos recibidos de la API:", datos.cursos);

                    if (!respuesta.ok || datos.error) {
                        throw new Error(
                            datos.mensaje || "No fue posible consultar los cursos."
                        );
                    }

                    contenedorCursos.innerHTML = "";

                    if (!datos.cursos || datos.cursos.length === 0) {

                        const mensajeVacio = document.createElement("div");

                        mensajeVacio.className = "sin-cursos";

                        const titulo = document.createElement("h3");

                        titulo.textContent = "Todavía no tienes cursos";

                        const descripcion = document.createElement("p");

                        descripcion.textContent =
                            "Actualmente no tienes cursos registrados como maestro.";

                        mensajeVacio.appendChild(titulo);

                        mensajeVacio.appendChild(descripcion);

                        contenedorCursos.appendChild(mensajeVacio);

                        return;

                    }

                    datos.cursos.forEach(curso => {

                        // TARJETA

                        const tarjeta = document.createElement("article");

                        tarjeta.className = "tarjeta-curso";


                        // IMAGEN

                        const contenedorImagen = document.createElement("div");

                        contenedorImagen.className = "imagen-curso";

                        const imagen = document.createElement("img");

                        imagen.src = curso.imagen || "images/imagen.jpg";

                        imagen.alt = curso.titulo || "Curso";

                        imagen.onerror = () => {

                            imagen.onerror = null;

                            imagen.src = "images/imagen.jpg";

                        };

                        contenedorImagen.appendChild(imagen);


                        // INFORMACIÓN

                        const informacion = document.createElement("div");

                        informacion.className = "informacion-curso";


                        const titulo = document.createElement("h3");

                        titulo.textContent = curso.titulo || "";


                        const categoria = document.createElement("p");

                        categoria.className = "categoria";

                        categoria.textContent =
                            "Categoría: " + (curso.categoria || "");

                        // ESTADO DEL CURSO
const estado = document.createElement("p");

estado.className = "estado-curso";

if (Number(curso.estado) === 1) {

    estado.textContent = "Estado: Activo";

    estado.classList.add("curso-activo");

} else {

    estado.textContent = "Estado: Dado de baja";

    estado.classList.add("curso-inactivo");

}

                        const descripcion = document.createElement("p");

                        descripcion.textContent = curso.descripcion || "";


                        const duracion = document.createElement("p");

                        duracion.textContent =
                            "Duración: " + (curso.duracion || "");


                        const precio = document.createElement("p");

                        precio.className = "precio";

                        precio.textContent =
                            "$" +
                            Number(curso.precio).toLocaleString("es-MX", {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }) +
                            " MXN";


                        // BOTONES

                        const botones = document.createElement("div");

                        botones.className = "botones";


                        const verCurso = document.createElement("a");

                        verCurso.href =
                            "curso_ver.php?id=" +
                            encodeURIComponent(curso.id);

                        verCurso.className = "boton";

                        verCurso.textContent = "Ver curso";


                        const administrar = document.createElement("a");

                        administrar.href =
                            "multimedia.php?id=" +
                            encodeURIComponent(curso.id);

                        administrar.className = "boton administrar";

                        administrar.textContent = "Administrar contenido";

                        // BOTÓN EDITAR
const editar = document.createElement("a");

editar.href =
    "curso_editar.php?id=" +
    encodeURIComponent(curso.id);

editar.className = "boton editar";

editar.textContent = "Editar curso";


// BOTÓN DAR DE BAJA
const baja = document.createElement("button");
baja.type = "button";
baja.className = "boton eliminar";

if (Number(curso.estado) === 1) {
    baja.textContent = "Dar de baja";
    baja.dataset.estado = "0";
} else {
    baja.textContent = "Reactivar curso";
    baja.dataset.estado = "1";
}

baja.dataset.id = curso.id;


                        botones.appendChild(verCurso);

botones.appendChild(administrar);

botones.appendChild(editar);

botones.appendChild(baja);

                        informacion.appendChild(titulo);

informacion.appendChild(categoria);

informacion.appendChild(estado);

informacion.appendChild(descripcion);

                        informacion.appendChild(duracion);

                        informacion.appendChild(precio);

                        informacion.appendChild(botones);


                        tarjeta.appendChild(contenedorImagen);

                        tarjeta.appendChild(informacion);

                        contenedorCursos.appendChild(tarjeta);

                    });

                } catch (error) {

                    console.error("Error al consultar cursos:", error);

                    contenedorCursos.innerHTML = "";

                    const errorElemento = document.createElement("p");

                    errorElemento.className = "mensaje-error";

                    errorElemento.textContent =
                        "No fue posible cargar tus cursos. Intenta nuevamente.";

                    contenedorCursos.appendChild(errorElemento);

                }

            }

            // ==========================================
// ELIMINAR CURSO MEDIANTE DELETE
// ==========================================

document.addEventListener("click", async (e) => {

    const boton = e.target.closest(".eliminar");

    if (!boton) return;

    const idCurso = boton.dataset.id;
    const nuevoEstado = Number(boton.dataset.estado);

    const reactivar = nuevoEstado === 1;

    const mensajeConfirmacion = reactivar
        ? "¿Deseas reactivar este curso? Volverá a estar disponible para los estudiantes."
        : "¿Estás seguro de que deseas dar de baja este curso?";

    if (!confirm(mensajeConfirmacion)) {
        return;
    }

    boton.disabled = true;

    boton.textContent = reactivar
        ? "Reactivando..."
        : "Dando de baja...";

    try {

        const respuesta = await fetch(
    `${API}?id=${encodeURIComponent(idCurso)}&estado=${nuevoEstado}`,
    {
        method: "DELETE",
        credentials: "same-origin",
        headers: {
            "X-CSRF-Token": CSRF_TOKEN
        }
    }
);

        const resultado = await respuesta.json();

        if (!respuesta.ok || resultado.error) {
            throw new Error(
                resultado.mensaje || "No se pudo cambiar el estado del curso."
            );
        }

        mostrarMensaje(
            reactivar
                ? "El curso se reactivó correctamente."
                : "El curso se dio de baja correctamente.",
            "mensaje-exito"
        );

        await cargarCursos();

    } catch (error) {

        console.error(error);

        mostrarMensaje(
            error.message || "Error al cambiar el estado del curso.",
            "mensaje-error"
        );

        boton.disabled = false;

        boton.textContent = reactivar
            ? "Reactivar curso"
            : "Dar de baja";
    }
});


            // ==========================================
            // CREAR CURSO MEDIANTE POST Y FORMDATA
            // ==========================================

            formulario.addEventListener("submit", async (evento) => {

                evento.preventDefault();

                mostrarMensaje("", "");

                botonCrear.disabled = true;

                botonCrear.textContent = "Creando curso...";

                try {

                    const formularioDatos = new FormData(formulario);

                    const respuesta = await fetch(API, {

    method: "POST",

    credentials: "same-origin",

    headers: {
        "X-CSRF-Token": CSRF_TOKEN
    },

    body: formularioDatos

});

                    const datos = await respuesta.json();

                    if (!respuesta.ok || datos.error) {

                        throw new Error(
                            datos.mensaje || "No fue posible crear el curso."
                        );

                    }

                    mostrarMensaje(
                        "El curso se creó correctamente.",
                        "mensaje-exito"
                    );

                    formulario.reset();

                    // Actualizar listado sin recargar la página
                    await cargarCursos();

                } catch (error) {

                    console.error("Error al crear curso:", error);

                    mostrarMensaje(
                        error.message || "Ocurrió un error al crear el curso.",
                        "mensaje-error"
                    );

                } finally {

                    botonCrear.disabled = false;

                    botonCrear.textContent = "Crear curso";

                }

            });


            // ==========================================
            // INICIAR CONSULTAS
            // ==========================================

            cargarCategorias();

            cargarCursos();

        });

    </script>

</body>

</html>
