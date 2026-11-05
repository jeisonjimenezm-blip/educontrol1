<?php
/* =========================================================
 * Envío del mensaje de confirmación por WhatsApp o SMS
 * ========================================================= */
require_once __DIR__ . '/../config.php';

function armar_mensaje(array $d): string
{
    return "Hola, {$d['nombres']}. Tu inscripción al programa {$d['programa']} ({$d['tipo']}) en el "
         . INSTITUCION . " fue registrada con éxito el {$d['fecha']}. "
         . "Número de inscripción: {$d['codigo']}. Pronto te contactaremos con los siguientes pasos.";
}

/* Convierte 3001234567 en 573001234567 */
function numero_internacional(string $celular): string
{
    $solo = preg_replace('/\D/', '', $celular);
    return (strlen($solo) === 10) ? INDICATIVO_PAIS . $solo : $solo;
}

/**
 * Envía la confirmación y devuelve el canal usado:
 * 'whatsapp', 'sms', 'simulado' o 'fallido'
 */
function enviar_confirmacion(string $celular, string $mensaje): string
{
    $numero = numero_internacional($celular);

    if (MODO_MENSAJES === 'simulado') {
        guardar_log("SIMULADO -> +$numero: $mensaje");
        return 'simulado';
    }

    if (MODO_MENSAJES === 'whatsapp' && enviar_whatsapp($numero, $mensaje)) {
        guardar_log("WHATSAPP -> +$numero");
        return 'whatsapp';
    }

    // Respaldo: si WhatsApp falla (o el modo es 'sms'), se intenta por SMS
    if (enviar_sms($numero, $mensaje)) {
        guardar_log("SMS -> +$numero");
        return 'sms';
    }

    guardar_log("FALLIDO -> +$numero");
    return 'fallido';
}

/* WhatsApp Business Cloud API (Meta) */
function enviar_whatsapp(string $numero, string $mensaje): bool
{
    if (WA_TOKEN === '' || WA_PHONE_ID === '') return false;

    $url = 'https://graph.facebook.com/' . WA_API_VERSION . '/' . WA_PHONE_ID . '/messages';
    $cuerpo = json_encode([
        'messaging_product' => 'whatsapp',
        'to'   => $numero,
        'type' => 'text',
        'text' => ['body' => $mensaje],
    ]);
    // Nota: para escribir a alguien que no le ha escrito antes al número de la empresa, Meta exige
    // usar una plantilla aprobada ('type' => 'template'). Para pruebas, el número destino debe estar
    // registrado como número de prueba en el panel de Meta.

    return peticion_http($url, $cuerpo, [
        'Authorization: Bearer ' . WA_TOKEN,
        'Content-Type: application/json',
    ]);
}

/* SMS con Twilio */
function enviar_sms(string $numero, string $mensaje): bool
{
    if (TWILIO_SID === '' || TWILIO_TOKEN === '' || TWILIO_FROM === '') return false;

    $url = 'https://api.twilio.com/2010-04-01/Accounts/' . TWILIO_SID . '/Messages.json';
    $cuerpo = http_build_query(['To' => '+' . $numero, 'From' => TWILIO_FROM, 'Body' => $mensaje]);

    return peticion_http($url, $cuerpo, [], TWILIO_SID . ':' . TWILIO_TOKEN);
}

function peticion_http(string $url, string $cuerpo, array $cabeceras, ?string $auth = null): bool
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $cuerpo,
        CURLOPT_HTTPHEADER     => $cabeceras,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    if ($auth) curl_setopt($ch, CURLOPT_USERPWD, $auth);
    $respuesta = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($codigo < 200 || $codigo >= 300) {
        guardar_log("Error HTTP $codigo en $url: " . substr((string)$respuesta, 0, 300));
        return false;
    }
    return true;
}

function guardar_log(string $linea): void
{
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    @file_put_contents($dir . '/mensajes.log', '[' . date('Y-m-d H:i:s') . "] $linea\n", FILE_APPEND);
}
