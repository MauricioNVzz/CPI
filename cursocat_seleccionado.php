<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Animación 2D</title>

    <link rel="stylesheet" href="cursocat_seleccionado.css">
</head>

<body>

    <script>
        const parametros = new URLSearchParams(window.location.search);

    const id = parametros.get("id");

    fetch(`api/api.php?id=${id}`)
    .then(respuesta => respuesta.json())
    .then(data => {

        if (data.error) {
            document.getElementById("curso").innerHTML =
                `<h2>${data.mensaje}</h2>`;

            return;
        }

        const curso = data.curso;

        document.getElementById("curso").innerHTML = `
            <h1>${curso.titulo}</h1>

            <p>${curso.descripcion}</p>

            <p>Instructor: ${curso.instructor}</p>

            <p>Categoría: ${curso.categoria}</p>

            <p>Duración: ${curso.duracion}</p>

            <h2>$${curso.precio}</h2>
        `;
    });
    </script>

    <div class="ventana-animacion">

        <!-- PARTE SUPERIOR -->

        <div class="barra-superior">

            <h1>Animación 2D</h1>

            <div class="foto-perfil">
                <img src="" alt="Foto de perfil">
            </div>

        </div>


        <!-- CONTENIDO -->

        <div class="contenido">

         <button class="tarjeta-curso">

    <h2>animación de cuerpo</h2>

    <p class="tutor">
        tutor:
        <a href="#">Carlos</a>
    </p>

    <p class="descripcion">
        aquí aprenderás lo esencial para animar personajes en 2D.
    </p>

    <p class="precio">
        49.00
    </p>

</button>

<button class="tarjeta-curso">

    <h2>expresiones de personajes</h2>

    <p class="tutor">
        tutor:
        <a href="#">Juan</a>
    </p>

    <p class="descripcion">
        aprenderás a crear expresiones faciales como corporales que caracterizan al personaje
    </p>

    <p class="precio">
        27.00
    </p>

</button>