
<?php

require_once __DIR__ . "/middleware/auth.php";

verificarAcceso([1, 2]);

$usuarioId = (int) $_SESSION["usuario_id"];
$rol = (int) $_SESSION["usuario_rol"];
$nombre = $_SESSION["usuario_nombre"] ?? "Usuario";

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mensajes | CPI</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f2f4f8;
            color: #222;
        }

        .contenedor {
            width: 95%;
            max-width: 1100px;
            margin: 35px auto;
        }

        .encabezado {
            margin-bottom: 20px;
        }

        .encabezado h1 {
            margin-bottom: 5px;
        }

        .encabezado p {
            color: #666;
        }

        .chat {
            display: grid;
            grid-template-columns: 280px 1fr;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            min-height: 600px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, .08);
        }

        .lista-maestros {
            border-right: 1px solid #ddd;
            background: #fafafa;
        }

        .lista-maestros h3 {
            padding: 20px;
            margin: 0;
            border-bottom: 1px solid #ddd;
        }

        .maestro {
            width: 100%;
            border: none;
            padding: 15px;
            text-align: left;
            background: transparent;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }

        .maestro:hover,
        .maestro.activo {
            background: #e8eefc;
        }

        .maestro strong {
            display: block;
            margin-bottom: 5px;
        }

        .maestro small {
            color: #666;
        }

        .area-chat {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .chat-encabezado {
            padding: 18px;
            border-bottom: 1px solid #ddd;
            min-height: 70px;
        }

        .chat-encabezado h3 {
            margin: 0;
        }

        .mensajes {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            min-height: 400px;
            max-height: 500px;
        }

        .mensaje {
            max-width: 75%;
            padding: 12px 15px;
            border-radius: 12px;
            overflow-wrap: anywhere;
        }

        .mensaje.propio {
            align-self: flex-end;
            background: #dce8ff;
        }

        .mensaje.recibido {
            align-self: flex-start;
            background: #eeeeee;
        }

        .mensaje p {
            margin: 0 0 7px;
            white-space: pre-wrap;
        }

        .mensaje small {
            color: #666;
            font-size: 11px;
        }

        .sin-mensajes {
            margin: auto;
            text-align: center;
            color: #777;
        }

        .formulario {
            display: flex;
            gap: 10px;
            padding: 15px;
            border-top: 1px solid #ddd;
        }

        .formulario textarea {
            flex: 1;
            resize: none;
            min-height: 45px;
            max-height: 120px;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-family: inherit;
        }

        .formulario button {
            border: none;
            background: #2458b8;
            color: white;
            padding: 0 22px;
            border-radius: 8px;
            cursor: pointer;
        }

        .formulario button:hover {
            background: #19438f;
        }

        .formulario button:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        .estado {
            padding: 10px 15px;
            color: #666;
            font-size: 13px;
        }

        .oculto {
            display: none;
        }

        @media (max-width: 700px) {
            .chat {
                grid-template-columns: 1fr;
            }

            .lista-maestros {
                border-right: none;
                border-bottom: 1px solid #ddd;
                max-height: 200px;
                overflow-y: auto;
            }

            .mensajes {
                min-height: 350px;
            }

            .mensaje {
                max-width: 90%;
            }
        }
    </style>
</head>

<body>

    <main class="contenedor">

        <div class="encabezado">
            <h1>Mensajes</h1>
            <p>Bienvenido, <?= htmlspecialchars($nombre, ENT_QUOTES, "UTF-8") ?></p>
        </div>

        <section class="chat">

            <?php if ($rol === 1): ?>

                <aside class="lista-maestros">
                    <h3>Maestros</h3>
                    <div id="listaMaestros">
                        <p class="estado">Cargando maestros...</p>
                    </div>
                </aside>

            <?php endif; ?>

            <section class="area-chat">

                <header class="chat-encabezado">
                    <h3 id="nombreConversacion">
                        <?php if ($rol === 2): ?>
                            Conversación con el administrador
                        <?php else: ?>
                            Selecciona un maestro
                        <?php endif; ?>
                    </h3>
                </header>

                <div class="mensajes" id="mensajes">
                    <p class="sin-mensajes">
                        <?php if ($rol === 2): ?>
                            Cargando conversación...
                        <?php else: ?>
                            Selecciona un maestro para consultar sus mensajes.
                        <?php endif; ?>
                    </p>
                </div>

                <div id="estado" class="estado"></div>

                <form class="formulario" id="formularioMensaje">
                    <textarea
                        id="contenido"
                        placeholder="Escribe un mensaje..."
                        maxlength="5000"
                        required
                        disabled
                    ></textarea>

                    <button type="submit" id="btnEnviar" disabled>
                        Enviar
                    </button>
                </form>

            </section>

        </section>

    </main>

    <script>
        const API = "api/mensajes.php";

        const usuarioId = <?= $usuarioId ?>;
        const rol = <?= $rol ?>;

        const listaMaestros = document.getElementById("listaMaestros");
        const mensajesContenedor = document.getElementById("mensajes");
        const nombreConversacion = document.getElementById("nombreConversacion");
        const formulario = document.getElementById("formularioMensaje");
        const contenido = document.getElementById("contenido");
        const btnEnviar = document.getElementById("btnEnviar");
        const estado = document.getElementById("estado");

        let maestroSeleccionado = null;

        // Escapar el contenido como texto para evitar interpretar HTML.
        function crearTexto(elemento, texto) {
            elemento.textContent = texto ?? "";
        }

        // Mostrar mensajes de la conversación.
        function mostrarMensajes(datos) {

            mensajesContenedor.replaceChildren();

            if (!datos || datos.length === 0) {
                const vacio = document.createElement("p");
                vacio.className = "sin-mensajes";
                vacio.textContent = "Todavía no hay mensajes. ¡Inicia la conversación!";
                mensajesContenedor.appendChild(vacio);
                return;
            }

            datos.forEach(mensaje => {

                const contenedor = document.createElement("div");

                contenedor.className =
                    Number(mensaje.Mensaje_id_emisor) === usuarioId
                        ? "mensaje propio"
                        : "mensaje recibido";

                const texto = document.createElement("p");
                crearTexto(texto, mensaje.Mensaje_contenido);

                const fecha = document.createElement("small");

                const fechaMensaje = new Date(
                    mensaje.Mensaje_fecha.replace(" ", "T")
                );

                fecha.textContent = Number.isNaN(fechaMensaje.getTime())
                    ? mensaje.Mensaje_fecha
                    : fechaMensaje.toLocaleString("es-MX");

                contenedor.appendChild(texto);
                contenedor.appendChild(fecha);

                mensajesContenedor.appendChild(contenedor);
            });

            mensajesContenedor.scrollTop = mensajesContenedor.scrollHeight;
        }

        // Consultar una conversación.
        async function cargarConversacion() {

            let url = API;

            if (rol === 1) {

                if (!maestroSeleccionado) return;

                url += "?usuario_id=" + encodeURIComponent(maestroSeleccionado.id);
            }

            try {

                const respuesta = await fetch(url, {
                    method: "GET",
                    credentials: "same-origin"
                });

                const resultado = await respuesta.json();

                if (!respuesta.ok) {
                    throw new Error(resultado.mensaje || "No fue posible cargar los mensajes.");
                }

                mostrarMensajes(resultado.mensajes);

                estado.textContent = "";

            } catch (error) {

                estado.textContent = error.message;

            }
        }

        // Cargar lista de maestros para el administrador.
        async function cargarMaestros() {

            try {

                const respuesta = await fetch(API + "?maestros=1", {
                    method: "GET",
                    credentials: "same-origin"
                });

                const resultado = await respuesta.json();

                if (!respuesta.ok) {
                    throw new Error(resultado.mensaje || "No fue posible cargar los maestros.");
                }

                listaMaestros.replaceChildren();

                if (resultado.maestros.length === 0) {
                    listaMaestros.textContent = "No hay maestros registrados.";
                    return;
                }

                resultado.maestros.forEach(maestro => {

                    const boton = document.createElement("button");
                    boton.type = "button";
                    boton.className = "maestro";

                    const nombre = document.createElement("strong");
                    nombre.textContent = maestro.nombre;

                    const email = document.createElement("small");
                    email.textContent = maestro.email;

                    boton.appendChild(nombre);
                    boton.appendChild(email);

                    boton.addEventListener("click", () => {

                        maestroSeleccionado = maestro;

                        document.querySelectorAll(".maestro").forEach(elemento => {
                            elemento.classList.remove("activo");
                        });

                        boton.classList.add("activo");

                        nombreConversacion.textContent = maestro.nombre;

                        contenido.disabled = false;
                        btnEnviar.disabled = false;

                        cargarConversacion();
                    });

                    listaMaestros.appendChild(boton);

                });

            } catch (error) {

                listaMaestros.textContent = error.message;

            }
        }

        // Enviar mensaje.
        formulario.addEventListener("submit", async function(evento) {

            evento.preventDefault();

            const texto = contenido.value.trim();

            if (!texto) return;

            if (rol === 1 && !maestroSeleccionado) {
                estado.textContent = "Selecciona un maestro primero.";
                return;
            }

            const datos = {
                contenido: texto
            };

            if (rol === 1) {
                datos.usuario_id = maestroSeleccionado.id;
            }

            try {

                btnEnviar.disabled = true;
                estado.textContent = "Enviando mensaje...";

                const respuesta = await fetch(API, {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(datos)
                });

                const resultado = await respuesta.json();

                if (!respuesta.ok) {
                    throw new Error(resultado.mensaje || "No fue posible enviar el mensaje.");
                }

                contenido.value = "";

                estado.textContent = "Mensaje enviado correctamente.";

                await cargarConversacion();

            } catch (error) {

                estado.textContent = error.message;

            } finally {

                btnEnviar.disabled = rol === 1 && !maestroSeleccionado;

            }

        });

        // Permitir enviar con Enter y saltar línea con Shift + Enter.
        contenido.addEventListener("keydown", function(evento) {

            if (evento.key === "Enter" && !evento.shiftKey) {
                evento.preventDefault();
                formulario.requestSubmit();
            }

        });

        // Inicio de la página.
        if (rol === 1) {

            cargarMaestros();

        } else {

            contenido.disabled = false;
            btnEnviar.disabled = false;

            cargarConversacion();

        }
    </script>

</body>
</html>
