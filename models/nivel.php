<?php

class Nivel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function crear(
    $cursoId,
    $nombre,
    $descripcion,
    $orden
) {
    $sql = "
        INSERT INTO niveles
        (
            Nivel_id_curso,
            Nivel_nombre,
            Nivel_descripcion,
            Nivel_orden
        )
        VALUES (?, ?, ?, ?)
    ";

    $stmt = $this->conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception(
            "Error al preparar la creación del nivel."
        );
    }

    $stmt->bind_param(
        "issi",
        $cursoId,
        $nombre,
        $descripcion,
        $orden
    );

    $stmt->execute();

    $id = $stmt->insert_id;

    $stmt->close();

    return $id;
}

public function actualizar(
    $nivelId,
    $cursoId,
    $nombre,
    $descripcion,
    $orden
) {
    $sql = "
        UPDATE niveles
        SET
            Nivel_nombre = ?,
            Nivel_descripcion = ?,
            Nivel_orden = ?
        WHERE
            Nivel_id = ?
            AND Nivel_id_curso = ?
    ";

    $stmt = $this->conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception(
            "Error al preparar la actualización del nivel."
        );
    }

    $stmt->bind_param(
        "ssiii",
        $nombre,
        $descripcion,
        $orden,
        $nivelId,
        $cursoId
    );

    $stmt->execute();

    $actualizados = $stmt->affected_rows;

    $stmt->close();

    return $actualizados;
}

    public function obtenerPorCurso($cursoId)
    {
        $sql = "
            SELECT
                Nivel_id,
                Nivel_id_curso,
                Nivel_nombre,
                Nivel_descripcion,
                Nivel_orden
            FROM niveles
            WHERE Nivel_id_curso = ?
            ORDER BY Nivel_orden ASC, Nivel_id ASC
        ";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "Error al consultar los niveles del curso."
            );
        }

        $stmt->bind_param("i", $cursoId);
        $stmt->execute();

        $resultado = $stmt->get_result();

        $niveles = [];

        while ($fila = $resultado->fetch_assoc()) {

            $niveles[] = [
                "id" => (int)$fila["Nivel_id"],
                "curso_id" => (int)$fila["Nivel_id_curso"],
                "nombre" => $fila["Nivel_nombre"],
                "descripcion" => $fila["Nivel_descripcion"],
                "orden" => (int)$fila["Nivel_orden"]
            ];
        }

        $stmt->close();

        return $niveles;
    }
}
?>