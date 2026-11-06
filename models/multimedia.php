<?php

class Multimedia
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerPorCurso($cursoId)
    {
        $sql = "
            SELECT
                Multimedia_id,
                Multimedia_id_curso,
                Multimedia_tipo,
                Multimedia_archivo,
                Multimedia_titulo,
                Multimedia_descripcion,
                Multimedia_orden,
                Multimedia_gratuito
            FROM Multimedia
            WHERE Multimedia_id_curso = ?
            ORDER BY Multimedia_orden ASC
        ";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "Error al preparar la consulta de multimedia."
            );
        }

        $stmt->bind_param(
            "i",
            $cursoId
        );

        $stmt->execute();

        $resultado = $stmt->get_result();

        $multimedia = [];

        while ($fila = $resultado->fetch_assoc()) {

            $multimedia[] = [
                "id" =>
                    (int)$fila["Multimedia_id"],

                "curso_id" =>
                    (int)$fila["Multimedia_id_curso"],

                "tipo" =>
                    $fila["Multimedia_tipo"],

                "archivo" =>
                    $fila["Multimedia_archivo"],

                "titulo" =>
                    $fila["Multimedia_titulo"],

                "descripcion" =>
                    $fila["Multimedia_descripcion"],

                "orden" =>
                    (int)$fila["Multimedia_orden"],

                "gratuito" =>
                    (bool)$fila["Multimedia_gratuito"]
            ];
        }

        $stmt->close();

        return $multimedia;
    }

    public function obtenerPorNivel($nivelId)
{
    $sql = "
        SELECT
            Multimedia_id,
            Multimedia_id_curso,
            Multimedia_id_nivel,
            Multimedia_tipo,
            Multimedia_archivo,
            Multimedia_titulo,
            Multimedia_descripcion,
            Multimedia_orden,
            Multimedia_gratuito
        FROM Multimedia
        WHERE Multimedia_id_nivel = ?
        ORDER BY Multimedia_orden ASC
    ";

    $stmt = $this->conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception(
            "Error al consultar la multimedia del nivel."
        );
    }

    $stmt->bind_param("i", $nivelId);
    $stmt->execute();

    $resultado = $stmt->get_result();

    $multimedia = [];

    while ($fila = $resultado->fetch_assoc()) {

        $multimedia[] = [
            "id" => (int)$fila["Multimedia_id"],
            "curso_id" => (int)$fila["Multimedia_id_curso"],
            "nivel_id" => (int)$fila["Multimedia_id_nivel"],
            "tipo" => $fila["Multimedia_tipo"],
            "archivo" => $fila["Multimedia_archivo"],
            "titulo" => $fila["Multimedia_titulo"],
            "descripcion" => $fila["Multimedia_descripcion"],
            "orden" => (int)$fila["Multimedia_orden"],
            "gratuito" => (bool)$fila["Multimedia_gratuito"]
        ];
    }

    $stmt->close();

    return $multimedia;
}

    public function agregar(
    $cursoId,
    $nivelId,
    $tipo,
    $archivo,
    $titulo,
    $descripcion,
    $orden,
    $gratuito
) {
    $sql = "
        INSERT INTO Multimedia
        (
            Multimedia_id_curso,
            Multimedia_id_nivel,
            Multimedia_tipo,
            Multimedia_archivo,
            Multimedia_titulo,
            Multimedia_descripcion,
            Multimedia_orden,
            Multimedia_gratuito
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $this->conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception(
            "Error al preparar el registro de multimedia."
        );
    }

    $gratuito = (int)$gratuito;

    $stmt->bind_param(
        "iissssii",
        $cursoId,
        $nivelId,
        $tipo,
        $archivo,
        $titulo,
        $descripcion,
        $orden,
        $gratuito
    );

    $stmt->execute();

    $id = $stmt->insert_id;

    $stmt->close();

    return $id;
}

public function obtenerCursoDeMaestro($cursoId, $maestroId)
{
    $sql = "
        SELECT
            c.Curso_id,
            c.Curso_nombre
        FROM Cursos c
        WHERE c.Curso_id = ?
        AND c.Curso_id_maestro = ?
    ";

    $stmt = $this->conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception(
            "Error al comprobar la propiedad del curso."
        );
    }

    $stmt->bind_param("ii", $cursoId, $maestroId);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $curso = $resultado->fetch_assoc();

    $stmt->close();

    return $curso ?: null;
}
}
?>