<?php

require_once __DIR__ . "/../models/Comentario.php";

class ComentarioController
{
    private $comentario;

    public function __construct($conexion)
    {
        $this->comentario = new Comentario($conexion);
    }

    public function obtenerPorMultimedia($multimediaId)
    {
        return $this->comentario
            ->obtenerPorMultimedia((int) $multimediaId);
    }

    public function crear($usuarioId, $multimediaId, $contenido)
    {
        if ((int) $usuarioId <= 0 || (int) $multimediaId <= 0) {
            throw new InvalidArgumentException(
                "El usuario o la lección no son válidos."
            );
        }

        return $this->comentario->crear(
            (int) $usuarioId,
            (int) $multimediaId,
            $contenido
        );
    }

    
public function obtenerTodos()
{
    return $this->comentario->obtenerTodos();
}

    
    // Eliminar comentario con motivo
    public function eliminarConMotivo(
        $comentarioId,
        $adminId,
        $motivo
    ) {
        $comentarioId = (int) $comentarioId;
        $adminId = (int) $adminId;
        $motivo = trim($motivo);

        if ($comentarioId <= 0 || $adminId <= 0) {
            throw new InvalidArgumentException(
                "Los identificadores proporcionados no son válidos."
            );
        }

        if ($motivo === "") {
            throw new InvalidArgumentException(
                "Debes proporcionar el motivo de eliminación."
            );
        }

        if (mb_strlen($motivo) > 500) {
            throw new InvalidArgumentException(
                "El motivo no puede superar los 500 caracteres."
            );
        }

        return $this->comentario->eliminarConMotivo(
            $comentarioId,
            $adminId,
            $motivo
        );
    }

}