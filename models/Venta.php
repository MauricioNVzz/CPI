<?php

class Venta
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    // Registrar una venta pendiente vinculada a PayPal.
    public function registrarVentaPendiente(
        $usuarioId,
        $cursoId,
        $costo,
        $paypalOrderId
    ) {
        $sql = "
            INSERT INTO Ventas
            (
                Venta_id_usuario,
                Venta_id_curso,
                Venta_costo,
                Venta_estado,
                Venta_paypal_order_id
            )
            VALUES (?, ?, ?, 'Pendiente', ?)
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param(
            "iids",
            $usuarioId,
            $cursoId,
            $costo,
            $paypalOrderId
        );

        $stmt->execute();

        $ventaId = $stmt->insert_id;

        $stmt->close();

        return $ventaId;
    }

    // Buscar una venta pendiente de un usuario para un curso.
    public function obtenerVentaPendiente($usuarioId, $cursoId)
    {
        $sql = "
            SELECT
                Venta_id,
                Venta_id_usuario,
                Venta_id_curso,
                Venta_costo,
                Venta_estado,
                Venta_paypal_order_id
            FROM Ventas
            WHERE Venta_id_usuario = ?
              AND Venta_id_curso = ?
              AND Venta_estado = 'Pendiente'
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "Error al buscar la venta pendiente."
            );
        }

        $stmt->bind_param("ii", $usuarioId, $cursoId);
        $stmt->execute();

        $resultado = $stmt->get_result();
        $venta = $resultado->fetch_assoc();

        $stmt->close();

        return $venta ?: null;
    }

    // Buscar una venta por su orden de PayPal y su propietario.
    public function obtenerPorOrdenPayPal(
        $paypalOrderId,
        $usuarioId
    ) {
        $sql = "
            SELECT
                Venta_id,
                Venta_id_usuario,
                Venta_id_curso,
                Venta_costo,
                Venta_estado,
                Venta_paypal_order_id
            FROM Ventas
            WHERE Venta_paypal_order_id = ?
            AND Venta_id_usuario = ?
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param(
            "si",
            $paypalOrderId,
            $usuarioId
        );

        $stmt->execute();

        $resultado = $stmt->get_result();
        $venta = $resultado->fetch_assoc();

        $stmt->close();

        return $venta ?: null;
    }

    // Marcar como completada una venta pendiente.
    public function completarVenta(
        $ventaId,
        $usuarioId,
        $paypalOrderId
    ) {
        $sql = "
            UPDATE Ventas
            SET Venta_estado = 'Completada'
            WHERE Venta_id = ?
            AND Venta_id_usuario = ?
            AND Venta_paypal_order_id = ?
            AND Venta_estado = 'Pendiente'
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param(
            "iis",
            $ventaId,
            $usuarioId,
            $paypalOrderId
        );

        $stmt->execute();

        $actualizadas = $stmt->affected_rows;

        $stmt->close();

        return $actualizadas === 1;
    }

    public function registrarVenta(
        $usuarioId,
        $cursoId,
        $costo
    ) {
        $sql = "
            INSERT INTO Ventas
            (
                Venta_id_usuario,
                Venta_id_curso,
                Venta_costo,
                Venta_estado
            )
            VALUES (?, ?, ?, 'Completada')
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param(
            "iid",
            $usuarioId,
            $cursoId,
            $costo
        );

        $stmt->execute();

        $ventaId = $stmt->insert_id;

        $stmt->close();

        return $ventaId;
    }

    public function comprobarCompra(
        $usuarioId,
        $cursoId
    ) {
        $sql = "
            SELECT Venta_id
            FROM Ventas
            WHERE Venta_id_usuario = ?
            AND Venta_id_curso = ?
            AND Venta_estado = 'Completada'
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param(
            "ii",
            $usuarioId,
            $cursoId
        );

        $stmt->execute();

        $resultado = $stmt->get_result();
        $comprado = $resultado->num_rows > 0;

        $stmt->close();

        return $comprado;
    }
}
?>