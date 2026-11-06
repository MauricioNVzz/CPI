<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cursos</title>

    <link rel="stylesheet" href="cursos_lista.css">
</head>

<body>

    <script>
        fetch("api/api.php")
            .then(respuesta => respuesta.json())
            .then(data => {
                const contenedor = document.getElementById("lista-cursos");
                data.cursos.forEach(curso => {
                    const elemento = document.createElement("div");
                    elemento.innerHTML = `
                        <h2>${curso.titulo}</h2>
                        <p>${curso.descripcion}</p>
                        <p>
                            <strong>Categoría:</strong>
                            ${curso.categoria}
                        </p>
                        <p>
                            <strong>Instructor:</strong>
                            ${curso.instructor}
                        </p>
                        <p>
                            <strong>Duración:</strong>
                            ${curso.duracion}
                        </p>
                        <p>
                            <strong>Precio:</strong>
                            $${curso.precio}
                        </p>
                        <hr>
                    `;
                    contenedor.appendChild(elemento);
                });

            })
            .catch(error => {
                console.error("Error:", error);
            });
    </script>


    <div class="ventana-cursos">

        <!-- PARTE SUPERIOR -->

        <div class="barra-superior">

            <h1>Cursos</h1>

            <div class="foto-perfil">
                <img src="" alt="Foto de perfil">
            </div>

        </div>


        <!-- CONTENIDO DE LOS CURSOS -->

     <div class="contenido">

    <h2>Mis cursos</h2>

    <div class="botones-cursos">

        <button>Animación 2D</button>

        <button>Modelado orgánico</button>

        <button>Animación 3D</button>

        <button>Edición</button>

    </div>

</div>