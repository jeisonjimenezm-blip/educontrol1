<?php
/* =========================================================
 * EduControl - Configuración general
 * ========================================================= */

// --- Base de datos (valores por defecto de XAMPP) ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'educontrol');
define('DB_USER', 'root');
define('DB_PASS', '');

// --- Institución ---
define('INSTITUCION', 'Centro Tecnológico de Cúcuta');
define('ZONA_HORARIA', 'America/Bogota');
date_default_timezone_set(ZONA_HORARIA);

// --- Mensajes de confirmación ---
// 'simulado' : no envía nada real, guarda el mensaje en logs/mensajes.log (ideal para la sustentación)
// 'whatsapp' : envía por la API de WhatsApp Business (Meta Cloud API) y, si falla, intenta SMS
// 'sms'      : envía solo por SMS (Twilio)
define('MODO_MENSAJES', 'simulado');

// Indicativo de Colombia
define('INDICATIVO_PAIS', '57');

// WhatsApp Business Cloud API (https://developers.facebook.com/docs/whatsapp/cloud-api)
define('WA_TOKEN', '');            // Token de acceso
define('WA_PHONE_ID', '');         // Phone number ID del número de WhatsApp Business
define('WA_API_VERSION', 'v21.0');

// Twilio SMS (https://www.twilio.com/docs/sms)
define('TWILIO_SID', '');
define('TWILIO_TOKEN', '');
define('TWILIO_FROM', '');         // Número de Twilio, ej. +15551234567
