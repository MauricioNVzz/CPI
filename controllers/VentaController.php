<?php

require_once __DIR__ . "/../models/Venta.php";

class VentaController
{
    private $venta;

    public function __construct($conexion)
    {
        $this->venta = new Venta($conexion);
    }

    public function registrarVentaPendiente(
        $usuarioId,
        $cursoId,
        $costo,
        $paypalOrderId
    ) {
        return $this->venta->registrarVentaPendiente(
            $usuarioId,
            $cursoId,
            $costo,
            $paypalOrderId
        );
    }

    public function obtenerVentaPendiente($usuarioId, $cursoId)
    {
        return $this->venta->obtenerVentaPendiente(
            $usuarioId,
            $cursoId
        );
    }

    public function obtenerPorOrdenPayPal(
        $paypalOrderId,
        $usuarioId
    ) {
        return $this->venta->obtenerPorOrdenPayPal(
            $paypalOrderId,
            $usuarioId
        );
    }

    public function completarVenta(
        $ventaId,
        $usuarioId,
        $paypalOrderId
    ) {
        return $this->venta->completarVenta(
            $ventaId,
            $usuarioId,
            $paypalOrderId
        );
    }

    public function registrarVenta(
        $usuarioId,
        $cursoId,
        $costo
    ) {
        return $this->venta->registrarVenta(
            $usuarioId,
            $cursoId,
            $costo
        );
    }

    public function comprobarCompra(
        $usuarioId,
        $cursoId
    ) {
        return $this->venta->comprobarCompra(
            $usuarioId,
            $cursoId
        );
    }
}
?>