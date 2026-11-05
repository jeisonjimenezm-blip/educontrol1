# EduControl

Plataforma web para la inscripción a **cursos, técnicas y auxiliares** del Centro Tecnológico de Cúcuta,
con confirmación automática por **WhatsApp o mensaje de texto (SMS)**.

Autor: **Jeison Johan Jiménez Mora** · Práctica Empresarial (PE-FR-13) · 2026

## Tecnologías
HTML5, CSS3, JavaScript, PHP 8 y MySQL (XAMPP).

## Estructura
```
educontrol/
├── index.php              Oferta + formulario de inscripción
├── inscribir.php          Valida, guarda la inscripción y envía la confirmación
├── config.php             Datos de conexión y de WhatsApp/SMS
├── database.sql           Base de datos con datos de ejemplo
├── includes/
│   ├── db.php             Conexión PDO y funciones de apoyo
│   ├── notificaciones.php Envío por WhatsApp (Meta) y SMS (Twilio)
│   ├── header.php / footer.php
├── admin/
│   ├── login.php          Ingreso del administrador
│   ├── index.php          Listado de inscritos, filtros y exportar a Excel
│   ├── programas.php      Crear, editar y activar/desactivar programas
│   ├── auth.php / logout.php
└── assets/css/styles.css, assets/js/app.js
```

## Instalación en XAMPP
1. Copie la carpeta `educontrol` dentro de `C:\xampp\htdocs\`.
2. Encienda **Apache** y **MySQL** en el panel de XAMPP.
3. Entre a `http://localhost/phpmyadmin`, pestaña **Importar**, y cargue `database.sql`.
4. Abra `http://localhost/educontrol`.

**Administrador:** `http://localhost/educontrol/admin` — usuario `admin`, contraseña `Admin2026*`.

## Mensajes de confirmación
En `config.php`, la constante `MODO_MENSAJES` define cómo se envía la confirmación:

| Modo | Qué hace |
|------|----------|
| `simulado` | No envía nada real: muestra el mensaje en pantalla y lo guarda en `logs/mensajes.log`. Ideal para la sustentación. |
| `whatsapp` | Envía por la API de WhatsApp Business (Meta). Si falla, intenta por SMS. |
| `sms` | Envía solo por SMS con Twilio. |

Para el envío real hay que crear una app en developers.facebook.com (WhatsApp Cloud API) y copiar el
token y el *Phone number ID* en `config.php`, o crear una cuenta en Twilio y copiar SID, token y número.
En modo de prueba, Meta solo permite enviar a números registrados como destinatarios de prueba.

## Funciones
- Oferta filtrable por cursos, técnicas y auxiliares, con cupos disponibles.
- Formulario con validación en el navegador y en el servidor.
- Autorización de tratamiento de datos (Ley 1581 de 2012).
- Evita inscripciones duplicadas de la misma persona en el mismo programa.
- Código de inscripción automático (ej. `EDU-2026-00001`).
- Confirmación por WhatsApp/SMS y registro del estado del envío.
- Panel administrativo con contraseña cifrada, filtros, búsqueda y exportación a Excel (CSV).
- Diseño adaptable a celulares.
