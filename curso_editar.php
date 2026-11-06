<?php

require_once __DIR__ . "/middleware/auth.php";

verificarAcceso([2]);
// Generar token CSRF si todavía no existe
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Comprobar que sea maestro
if (
    !isset($_SESSION["usuario_rol"]) ||
    (int)$_SESSION["usuario_rol"] !== 2
) {
    header("Location: cursos_inicio.php");
    exit;
}

// Obtener ID del curso
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if ($id === false || $id === null || $id <= 0) {
    header("Location: maestro.php");
    exit;
}

$nombreMaestro = $_SESSION["usuario_nombre"] ?? "Maestro";

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
    name="csrf-token"
    content="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>"
>

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Editar curso</title>

    <link
        rel="stylesheet"
        href="css/maestro.css"
    >

</head>

<body>

    <!-- BARRA DE NAVEGACIÓN -->

    <header class="barra-navegacion">

        <div class="logo">
            <a href="maestro.php">CPI</a>
        </div>

        <nav>

            <a href="cursos_inicio.php">
                Inicio
            </a>

            <a href="maestro.php">
                Mis cursos
            </a>

            <a href="perfil.php">
                Mi perfil
            </a>

            <a href="cerrar_sesion.php">
                Cerrar sesión
            </a>

        </nav>

    </header>


    <!-- CONTENIDO -->

    <main class="contenedor">

        <section class="encabezado">

            <h1>Editar curso</h1>

            <p>
                Bienvenido,
                <?= htmlspecialchars($nombreMaestro, ENT_QUOTES, 'UTF-8') ?>.
            </p>

            <p>
                Modifica la información de tu curso.
            </p>

        </section>


        <!-- FORMULARIO -->

        <section class="formulario-curso">

            <h3>Información del curso</h3>

            <p
                id="mensaje"
                aria-live="polite"
            ></p>

            <div id="cargando">
                Cargando información del curso...
            </div>

            <form
                id="formulario-editar"
                style="display: none;"
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
                        rows="5"
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
                        maxlength="100"
                        required
                    >

                </div>


                <!-- IMAGEN ACTUAL -->

                <div class="campo">

                    <label>
                        Imagen de portada actual:
                    </label>

                    <img
                        id="imagen-actual"
                        src="images/imagen.jpg"
                        alt="Imagen actual del curso"
                        style="
                            width: 100%;
                            max-width: 300px;
                            height: 180px;
                            object-fit: cover;
                            border-radius: 8px;
                        "
                    >

                    <div class="campo-formulario">
    <label for="imagen">Nueva imagen de portada</label>

    <input
        type="file"
        id="imagen"
        name="imagen"
        accept=".jpg,.jpeg,.png,.webp"
    >

    <small>Formatos permitidos: JPG, PNG y WEBP. Máximo 5 MB.</small>
</div>

<button type="button" id="btnActualizarImagen">
    Actualizar imagen
</button>

<p id="mensaje-imagen"></p>

                </div>


                <button
                    type="submit"
                    id="boton-guardar"
                >
                    Guardar cambios
                </button>

                <a
                    href="maestro.php"
                    class="boton"
                >
                    Cancelar
                </a>

            </form>

        </section>

    </main>


    <!-- FOOTER -->

    <footer>

        <p>
            © 2026 CPI Cursos
        </p>

    </footer>


    <!-- JAVASCRIPT -->

    <script>

        document.addEventListener("DOMContentLoaded", () => {

            const API = "api/api.php";

            const CSRF_TOKEN = document.querySelector(
    'meta[name="csrf-token"]'
).content;

            const idCurso = <?= json_encode($id) ?>;

            const btnActualizarImagen = document.getElementById("btnActualizarImagen");

btnActualizarImagen.addEventListener("click", async () => {

    const archivo = document.getElementById("imagen").files[0];
    const mensaje = document.getElementById("mensaje-imagen");

    if (!archivo) {
        mensaje.textContent = "Selecciona una imagen antes de continuar.";
        return;
    }

    if (archivo.size > 5 * 1024 * 1024) {
        mensaje.textContent = "La imagen no debe superar los 5 MB.";
        return;
    }

    const formulario = new FormData();

    formulario.append("accion", "actualizar_imagen");
    formulario.append("id", idCurso);
    formulario.append("imagen", archivo);

    try {

        btnActualizarImagen.disabled = true;
        mensaje.textContent = "Actualizando imagen...";

        const respuesta = await fetch(API, {
    method: "POST",
    credentials: "same-origin",
    headers: {
        "X-CSRF-Token": CSRF_TOKEN
    },
    body: formulario
});

        const resultado = await respuesta.json();

        if (!respuesta.ok) {
            throw new Error(resultado.mensaje || "No fue posible actualizar la imagen.");
        }

        mensaje.textContent = resultado.mensaje;

        // Actualizar la vista previa con la imagen nueva
        const imagenActual = document.getElementById("imagenActual");

        if (imagenActual && resultado.curso.imagen) {
            imagenActual.src = resultado.curso.imagen + "?t=" + Date.now();
        }

        // Limpiar selector
        document.getElementById("imagen").value = "";

    } catch (error) {

        mensaje.textContent = error.message;

    } finally {

        btnActualizarImagen.disabled = false;

    }
});

            const formulario = document.getElementById(
                "formulario-editar"
            );

            const mensaje = document.getElementById("mensaje");

            const cargando = document.getElementById("cargando");

            const botonGuardar = document.getElementById(
                "boton-guardar"
            );

            const selectCategoria = document.getElementById(
                "categoria"
            );

            const imagenActual = document.getElementById(
                "imagen-actual"
            );


            // ======================================
            // MOSTRAR MENSAJES
            // ======================================

            function mostrarMensaje(texto, tipo) {

                mensaje.textContent = texto;

                mensaje.className = tipo;

            }


            // ======================================
            // CARGAR CATEGORÍAS
            // ======================================

            async function cargarCategorias() {

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
                        datos.mensaje ||
                        "No fue posible cargar las categorías."
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

            }


            // ======================================
            // CARGAR DATOS DEL CURSO
            // ======================================

            async function cargarCurso() {

                try {

                    // Primero cargar las categorías
                    await cargarCategorias();

                    // Consultar curso
                    const respuesta = await fetch(
                        `${API}?id=${encodeURIComponent(idCurso)}`,
                        {
                            method: "GET",
                            credentials: "same-origin"
                        }
                    );

                    const datos = await respuesta.json();

                    if (!respuesta.ok || datos.error) {
                        throw new Error(
                            datos.mensaje ||
                            "No fue posible consultar el curso."
                        );
                    }

                    const curso = datos.curso;

                    if (!curso) {
                        throw new Error(
                            "La API no devolvió la información del curso."
                        );
                    }

                    // Llenar formulario
                    document.getElementById("titulo").value =
                        curso.titulo || "";

                    document.getElementById("descripcion").value =
                        curso.descripcion || "";

                    document.getElementById("precio").value =
                        curso.precio ?? "";

                    document.getElementById("duracion").value =
                        curso.duracion || "";


                    // Seleccionar categoría actual
                    // La consulta GET devuelve el nombre de la categoría.
                    const categoriaActual = Array.from(
                        selectCategoria.options
                    ).find(opcion =>
                        opcion.textContent === curso.categoria
                    );

                    if (categoriaActual) {

                        selectCategoria.value = categoriaActual.value;

                    } else {

                        throw new Error(
                            "No se encontró la categoría actual del curso."
                        );

                    }


                    // Mostrar imagen actual
                    if (curso.imagen) {

                        imagenActual.src = curso.imagen;

                        imagenActual.onerror = () => {

                            imagenActual.onerror = null;

                            imagenActual.src = "images/imagen.jpg";

                        };

                    }


                    // Mostrar formulario
                    cargando.style.display = "none";

                    formulario.style.display = "block";

                } catch (error) {

                    console.error(
                        "Error al cargar el curso:",
                        error
                    );

                    cargando.textContent = error.message;

                    mostrarMensaje(
                        error.message,
                        "mensaje-error"
                    );

                }

            }


            // ======================================
            // ACTUALIZAR CURSO MEDIANTE PUT
            // ======================================

            formulario.addEventListener("submit", async (evento) => {

                evento.preventDefault();

                mostrarMensaje("", "");

                botonGuardar.disabled = true;

                botonGuardar.textContent = "Guardando cambios...";

                try {

                    const datosCurso = {

                        titulo: document.getElementById("titulo").value.trim(),

                        descripcion: document.getElementById("descripcion").value.trim(),

                        categoria: Number(
                            selectCategoria.value
                        ),

                        precio: Number(
                            document.getElementById("precio").value
                        ),

                        duracion: document.getElementById("duracion").value.trim()

                    };


                    const respuesta = await fetch(
                        `${API}?id=${encodeURIComponent(idCurso)}`,
                        {
                            method: "PUT",

                            credentials: "same-origin",

                            headers: {
    "Content-Type": "application/json",
    "X-CSRF-Token": CSRF_TOKEN
},

                            body: JSON.stringify(datosCurso)
                        }
                    );


                    const resultado = await respuesta.json();

                    if (!respuesta.ok || resultado.error) {

                        throw new Error(
                            resultado.mensaje ||
                            "No fue posible actualizar el curso."
                        );

                    }


                    mostrarMensaje(
                        "El curso se actualizó correctamente. Regresando al panel...",
                        "mensaje-exito"
                    );


                    // Regresar al panel del maestro
                    setTimeout(() => {

                        window.location.href = "maestro.php";

                    }, 1500);


                } catch (error) {

                    console.error(
                        "Error al actualizar el curso:",
                        error
                    );

                    mostrarMensaje(
                        error.message ||
                        "Ocurrió un error al actualizar el curso.",
                        "mensaje-error"
                    );

                } finally {

                    botonGuardar.disabled = false;

                    botonGuardar.textContent = "Guardar cambios";

                }

            });


            // ======================================
            // INICIAR
            // ======================================

            cargarCurso();

        });

    </script>

</body>

</html>
