
<?php

class Certificado
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    // Generar certificado para un alumno
    public function generar($usuarioId, $cursoId)
    {
        // Comprobar si ya existe un certificado
        $sql = "
            SELECT Certificado_id, Certificado_folio
            FROM certificados
            WHERE Certificado_id_usuario = ?
              AND Certificado_id_curso = ?
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("Error al consultar el certificado.");
        }

        $stmt->bind_param("ii", $usuarioId, $cursoId);
        $stmt->execute();

        $resultado = $stmt->get_result();
        $existente = $resultado->fetch_assoc();

        $stmt->close();

        if ($existente) {
            return $existente["Certificado_folio"];
        }

        // Generar folio único
        $folio = "CPI-" . strtoupper(bin2hex(random_bytes(8)));

        $sql = "
            INSERT INTO certificados (
                Certificado_id_usuario,
                Certificado_id_curso,
                Certificado_folio
            )
            VALUES (?, ?, ?)
        ";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("Error al preparar el certificado.");
        }

        $stmt->bind_param(
            "iis",
            $usuarioId,
            $cursoId,
            $folio
        );

        if (!$stmt->execute()) {
            throw new Exception("No se pudo generar el certificado.");
        }

        $stmt->close();

        return $folio;
    }
}
?>
