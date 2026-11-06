
<?php

require_once __DIR__ . "/../models/valoracion.php";

class ValoracionController {

    private $valoracion;

    public function __construct($conexion) {
        $this->valoracion = new Valoracion($conexion);
    }

    public function obtenerResumen($cursoId) {
        return $this->valoracion->obtenerResumen($cursoId);
    }

    public function obtenerPorUsuario($usuarioId, $cursoId) {
        return $this->valoracion->obtenerPorUsuario($usuarioId, $cursoId);
    }

    public function guardar($usuarioId, $cursoId, $puntuacion) {
        return $this->valoracion->guardar(
            $usuarioId,
            $cursoId,
            $puntuacion
        );
    }
}

?>
