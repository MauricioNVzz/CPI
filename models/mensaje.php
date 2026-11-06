
<?php

class Mensaje
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    // Enviar un mensaje
    public function enviar($emisor, $receptor, $contenido)
    {
        $sql = "
            INSERT INTO Mensajes (
                Mensaje_id_emisor,
                Mensaje_id_receptor,
                Mensaje_contenido
            )
            VALUES (?, ?, ?)
        ";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("Error al preparar el envío del mensaje.");
        }

        $stmt->bind_param("iis", $emisor, $receptor, $contenido);

        if (!$stmt->execute()) {
            throw new Exception("Error al enviar el mensaje.");
        }

        $id = $stmt->insert_id;

        $stmt->close();

        return $id;
    }

    // Obtener conversación entre dos usuarios
    public function obtenerConversacion($usuario1, $usuario2)
    {
        $sql = "
            SELECT
                m.Mensaje_id,
                m.Mensaje_id_emisor,
                m.Mensaje_id_receptor,
                m.Mensaje_contenido,
                m.Mensaje_fecha,
                m.Mensaje_leido,
                em.Usuario_nombre AS emisor,
                re.Usuario_nombre AS receptor
            FROM Mensajes m
            INNER JOIN Usuarios em
                ON m.Mensaje_id_emisor = em.Usuario_id
            INNER JOIN Usuarios re
                ON m.Mensaje_id_receptor = re.Usuario_id
            WHERE
                (
                    m.Mensaje_id_emisor = ?
                    AND m.Mensaje_id_receptor = ?
                )
                OR
                (
                    m.Mensaje_id_emisor = ?
                    AND m.Mensaje_id_receptor = ?
                )
            ORDER BY m.Mensaje_fecha ASC, m.Mensaje_id ASC
        ";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("Error al preparar la consulta de mensajes.");
        }

        $stmt->bind_param(
            "iiii",
            $usuario1,
            $usuario2,
            $usuario2,
            $usuario1
        );

        $stmt->execute();

        $resultado = $stmt->get_result();

        $mensajes = [];

        while ($fila = $resultado->fetch_assoc()) {
            $mensajes[] = $fila;
        }

        $stmt->close();

        return $mensajes;
    }

    // Marcar como leídos los mensajes recibidos
    public function marcarLeidos($emisor, $receptor)
    {
        $sql = "
            UPDATE Mensajes
            SET Mensaje_leido = 1
            WHERE Mensaje_id_emisor = ?
            AND Mensaje_id_receptor = ?
            AND Mensaje_leido = 0
        ";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("Error al preparar la actualización de mensajes.");
        }

        $stmt->bind_param("ii", $emisor, $receptor);

        if (!$stmt->execute()) {
            throw new Exception("Error al marcar los mensajes como leídos.");
        }

        $actualizados = $stmt->affected_rows;

        $stmt->close();

        return $actualizados;
    }
}
