
<?php

class Valoracion {

    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    // Obtener promedio y cantidad de valoraciones de un curso
    public function obtenerResumen($cursoId) {

        $sql = "SELECT
                    COALESCE(AVG(Valoracion_puntuacion), 0) AS promedio,
                    COUNT(*) AS total
                FROM Valoraciones
                WHERE Valoracion_id_curso = ?";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $cursoId);
        $stmt->execute();

        $resultado = $stmt->get_result();
        $datos = $resultado->fetch_assoc();

        $stmt->close();

        return $datos;
    }

    // Obtener la valoración que un usuario ya hizo para un curso
    public function obtenerPorUsuario($usuarioId, $cursoId) {

        $sql = "SELECT Valoracion_puntuacion
                FROM Valoraciones
                WHERE Valoracion_id_usuario = ?
                AND Valoracion_id_curso = ?";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("ii", $usuarioId, $cursoId);
        $stmt->execute();

        $resultado = $stmt->get_result();
        $datos = $resultado->fetch_assoc();

        $stmt->close();

        return $datos ? (int)$datos["Valoracion_puntuacion"] : null;
    }

    // Guardar una valoración o actualizarla si ya existe
    public function guardar($usuarioId, $cursoId, $puntuacion) {

        $sql = "INSERT INTO Valoraciones (
                    Valoracion_id_usuario,
                    Valoracion_id_curso,
                    Valoracion_puntuacion,
                    Valoracion_fecha
                )
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    Valoracion_puntuacion = VALUES(Valoracion_puntuacion),
                    Valoracion_fecha = NOW()";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("iii", $usuarioId, $cursoId, $puntuacion);

        $resultado = $stmt->execute();

        $stmt->close();

        return $resultado;
    }
}
