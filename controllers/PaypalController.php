<?php

require_once __DIR__ . "/../config/paypal.php";

class PayPalController
{
    // Obtener Access Token de PayPal.
    public function obtenerAccessToken()
    {
        $url = PAYPAL_BASE_URL . "/v1/oauth2/token";

        $credenciales = base64_encode(
            PAYPAL_CLIENT_ID . ":" . PAYPAL_CLIENT_SECRET
        );

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => "grant_type=client_credentials",
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic " . $credenciales,
                "Content-Type: application/x-www-form-urlencoded"
            ],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30
        ]);

        $respuesta = curl_exec($ch);

        if ($respuesta === false) {
            $error = curl_error($ch);
            curl_close($ch);

            throw new Exception(
                "Error de conexión con PayPal al autenticar."
            );
        }

        $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $datos = json_decode($respuesta, true);

        if ($codigo !== 200 || !is_array($datos)) {
            throw new Exception(
                "PayPal no pudo completar la autenticación."
            );
        }

        if (
            empty($datos["access_token"]) ||
            !is_string($datos["access_token"])
        ) {
            throw new Exception(
                "PayPal no devolvió un Access Token válido."
            );
        }

        return $datos["access_token"];
    }

    // Crear una orden de PayPal.
    public function crearOrden($precio, $descripcion)
    {
        if (!is_numeric($precio) || !is_finite((float) $precio) || $precio < 0) {
            throw new Exception("El precio para crear la orden no es válido.");
        }

        $accessToken = $this->obtenerAccessToken();

        $url = PAYPAL_BASE_URL . "/v2/checkout/orders";

        $datos = [
            "intent" => "CAPTURE",
            "purchase_units" => [
                [
                    "amount" => [
                        "currency_code" => "MXN",
                        "value" => number_format(
                            (float) $precio,
                            2,
                            ".",
                            ""
                        )
                    ],
                    "description" => (string) $descripcion
                ]
            ]
        ];

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($datos),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer " . $accessToken
            ],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30
        ]);

        $respuesta = curl_exec($ch);

        if ($respuesta === false) {
            $error = curl_error($ch);
            curl_close($ch);

            error_log("Error cURL al crear orden PayPal: " . $error);

            throw new Exception(
                "No se pudo confirmar la creación de la orden con PayPal. Verifica su estado antes de intentar otra vez."
            );
        }

        $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $resultado = json_decode($respuesta, true);

        if (
            $codigo !== 201 ||
            !is_array($resultado) ||
            empty($resultado["id"])
        ) {
            error_log(
                "PayPal no creó la orden. HTTP: " . $codigo .
                "; respuesta: " . substr($respuesta, 0, 1000)
            );

            throw new Exception(
                "PayPal no pudo confirmar la creación de la orden."
            );
        }

        return $resultado;
    }

    // Consultar el estado actual de una orden de PayPal.
    public function consultarOrden($orderId)
    {
        if (
            !is_string($orderId) ||
            !preg_match('/^[A-Za-z0-9-]+$/', $orderId)
        ) {
            throw new Exception("El ID de la orden no es válido.");
        }

        $accessToken = $this->obtenerAccessToken();

        $url = PAYPAL_BASE_URL . "/v2/checkout/orders/" .
            rawurlencode($orderId);

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer " . $accessToken
            ],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30
        ]);

        $respuesta = curl_exec($ch);

        if ($respuesta === false) {
            $error = curl_error($ch);
            curl_close($ch);

            error_log("Error cURL al consultar orden PayPal: " . $error);

            throw new Exception(
                "No se pudo consultar el estado de la orden en PayPal."
            );
        }

        $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $resultado = json_decode($respuesta, true);

        if ($codigo !== 200 || !is_array($resultado)) {
            error_log(
                "PayPal no pudo consultar la orden. HTTP: " . $codigo .
                "; respuesta: " . substr($respuesta, 0, 1000)
            );

            throw new Exception(
                "PayPal no pudo confirmar el estado de la orden."
            );
        }

        if (
            !isset($resultado["id"]) ||
            $resultado["id"] !== $orderId
        ) {
            throw new Exception(
                "La orden consultada no coincide con la solicitada."
            );
        }

        return $resultado;
    }

    // Capturar una orden de PayPal.
    public function capturarOrden($orderId)
    {
        if (
            !is_string($orderId) ||
            !preg_match('/^[A-Za-z0-9-]+$/', $orderId)
        ) {
            throw new Exception("El ID de la orden no es válido.");
        }

        $accessToken = $this->obtenerAccessToken();

        $url = PAYPAL_BASE_URL . "/v2/checkout/orders/" .
            rawurlencode($orderId) . "/capture";

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer " . $accessToken
            ],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30
        ]);

        $respuesta = curl_exec($ch);

        if ($respuesta === false) {
            $error = curl_error($ch);
            curl_close($ch);

            error_log("Error cURL al capturar orden PayPal: " . $error);

            throw new Exception(
                "No se pudo confirmar el resultado del pago. Consulta el estado de la orden antes de volver a intentarlo."
            );
        }

        $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $resultado = json_decode($respuesta, true);

        if (!is_array($resultado)) {
            error_log(
                "Respuesta JSON no válida al capturar orden PayPal. HTTP: " .
                $codigo
            );

            throw new Exception(
                "PayPal devolvió una respuesta no válida al procesar el pago."
            );
        }

        if (
            $codigo !== 201 ||
            !isset($resultado["id"]) ||
            $resultado["id"] !== $orderId
        ) {
            error_log(
                "No se confirmó la captura de PayPal. HTTP: " . $codigo .
                "; respuesta: " . substr($respuesta, 0, 1000)
            );

            throw new Exception(
                "PayPal no confirmó la captura. Antes de volver a intentarlo, consulta el estado de la orden."
            );
        }

        return $resultado;
    }
}
?>