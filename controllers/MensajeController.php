
<?php

require_once __DIR__ . "/../models/mensaje.php";

class MensajeController
{
    private $mensaje;

    public function __construct($conexion)
    {
        $this->mensaje = new Mensaje($conexion);
    }

    // Enviar un mensaje
    public function enviar($emisor, $receptor, $contenido)
    {
        return $this->mensaje->enviar(
            $emisor,
            $receptor,
            $contenido
        );
    }

    // Obtener conversación
    public function obtenerConversacion($usuario1, $usuario2)
    {
        return $this->mensaje->obtenerConversacion(
            $usuario1,
            $usuario2
        );
    }

    // Marcar mensajes como leídos
    public function marcarLeidos($emisor, $receptor)
    {
        return $this->mensaje->marcarLeidos(
            $emisor,
            $receptor
        );
    }
}
