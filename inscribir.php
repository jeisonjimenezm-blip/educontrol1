<?php
/* EduControl - Procesa la inscripción y envía la confirmación */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/notificaciones.php';

session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }

$d = [
    'id_programa'    => (int)($_POST['id_programa'] ?? 0),
    'tipo_documento' => trim($_POST['tipo_documento'] ?? ''),
    'documento'      => preg_replace('/\D/', '', $_POST['documento'] ?? ''),
    'nombres'        => trim($_POST['nombres'] ?? ''),
    'apellidos'      => trim($_POST['apellidos'] ?? ''),
    'celular'        => preg_replace('/\D/', '', $_POST['celular'] ?? ''),
    'correo'         => trim($_POST['correo'] ?? ''),
    'autoriza_datos' => !empty($_POST['autoriza_datos']) ? 1 : 0,
];

// ---------- Validaciones ----------
$errores = [];
if (!csrf_valido($_POST['csrf'] ?? null))                          $errores[] = 'La sesión expiró. Intenta de nuevo.';
if (!in_array($d['tipo_documento'], ['CC', 'TI', 'CE', 'PPT'], true)) $errores[] = 'Selecciona un tipo de documento válido.';
if (!preg_match('/^\d{6,15}$/', $d['documento']))                  $errores[] = 'El documento debe tener entre 6 y 15 dígitos.';
if (mb_strlen($d['nombres']) < 2)                                  $errores[] = 'Escribe tus nombres.';
if (mb_strlen($d['apellidos']) < 2)                                $errores[] = 'Escribe tus apellidos.';
if (!preg_match('/^3\d{9}$/', $d['celular']))                      $errores[] = 'El celular debe tener 10 dígitos y empezar por 3.';
if (!filter_var($d['correo'], FILTER_VALIDATE_EMAIL))              $errores[] = 'Escribe un correo electrónico válido.';
if (!$d['autoriza_datos'])                                         $errores[] = 'Debes autorizar el tratamiento de datos personales.';

$pdo = db();
$programa = null;
if ($d['id_programa']) {
    $st = $pdo->prepare("SELECT p.*, (p.cupos - (SELECT COUNT(*) FROM inscripciones i WHERE i.id_programa = p.id_programa)) AS disponibles
                         FROM programas p WHERE p.id_programa = ? AND p.estado = 1");
    $st->execute([$d['id_programa']]);
    $programa = $st->fetch();
}
if (!$programa)                                  $errores[] = 'Selecciona un programa de la oferta.';
elseif ($programa['disponibles'] <= 0)           $errores[] = 'El programa seleccionado ya no tiene cupos.';

if ($errores) {
    $_SESSION['errores'] = $errores;
    $_SESSION['viejo'] = $d;
    header('Location: index.php#inscripcion');
    exit;
}

// ---------- Guardar ----------
$ahora = date('Y-m-d H:i:s');
try {
    $pdo->beginTransaction();

    // Si el aspirante ya existe (mismo documento) se actualizan sus datos de contacto
    $st = $pdo->prepare('SELECT id_aspirante FROM aspirantes WHERE documento = ?');
    $st->execute([$d['documento']]);
    $idAspirante = $st->fetchColumn();

    if ($idAspirante) {
        $pdo->prepare('UPDATE aspirantes SET tipo_documento=?, nombres=?, apellidos=?, celular=?, correo=?, autoriza_datos=1
                       WHERE id_aspirante=?')
            ->execute([$d['tipo_documento'], $d['nombres'], $d['apellidos'], $d['celular'], $d['correo'], $idAspirante]);
    } else {
        $pdo->prepare('INSERT INTO aspirantes (tipo_documento, documento, nombres, apellidos, celular, correo, autoriza_datos, fecha_registro)
                       VALUES (?,?,?,?,?,?,1,?)')
            ->execute([$d['tipo_documento'], $d['documento'], $d['nombres'], $d['apellidos'], $d['celular'], $d['correo'], $ahora]);
        $idAspirante = $pdo->lastInsertId();
    }

    // Evitar inscribir dos veces a la misma persona en el mismo programa
    $st = $pdo->prepare('SELECT codigo FROM inscripciones WHERE id_aspirante = ? AND id_programa = ?');
    $st->execute([$idAspirante, $d['id_programa']]);
    if ($codigoExistente = $st->fetchColumn()) {
        $pdo->rollBack();
        $_SESSION['errores'] = ["Ya estás inscrito en este programa (código $codigoExistente)."];
        $_SESSION['viejo'] = $d;
        header('Location: index.php#inscripcion');
        exit;
    }

    $pdo->prepare('INSERT INTO inscripciones (codigo, id_aspirante, id_programa, fecha) VALUES (?,?,?,?)')
        ->execute(['TMP-' . bin2hex(random_bytes(4)), $idAspirante, $d['id_programa'], $ahora]);
    $idInscripcion = (int)$pdo->lastInsertId();
    $codigo = sprintf('EDU-%s-%05d', date('Y'), $idInscripcion);
    $pdo->prepare('UPDATE inscripciones SET codigo = ? WHERE id_inscripcion = ?')->execute([$codigo, $idInscripcion]);

    $pdo->commit();
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['errores'] = ['No fue posible guardar la inscripción. Intenta de nuevo.'];
    $_SESSION['viejo'] = $d;
    header('Location: index.php#inscripcion');
    exit;
}

// ---------- Confirmación por WhatsApp / SMS ----------
$mensaje = armar_mensaje([
    'nombres'  => $d['nombres'],
    'programa' => $programa['nombre'],
    'tipo'     => tipo_texto($programa['tipo']),
    'fecha'    => date('d/m/Y'),
    'codigo'   => $codigo,
]);
$canal = enviar_confirmacion($d['celular'], $mensaje);
$pdo->prepare('UPDATE inscripciones SET estado_confirmacion = ? WHERE id_inscripcion = ?')->execute([$canal, $idInscripcion]);

$textosCanal = [
    'whatsapp' => 'Te enviamos la confirmación a tu WhatsApp.',
    'sms'      => 'Te enviamos la confirmación por mensaje de texto.',
    'simulado' => 'Modo demostración: el mensaje de confirmación quedó registrado en el sistema.',
    'fallido'  => 'Tu inscripción quedó guardada, pero no pudimos enviar el mensaje. Guarda tu código.',
];

$titulo = 'Inscripción registrada';
require __DIR__ . '/includes/header.php';
?>
<section class="seccion">
  <div class="contenedor contenedor--angosto">
    <div class="confirmacion">
      <div class="confirmacion__icono">&#10003;</div>
      <h1>¡Inscripción registrada!</h1>
      <p><?= e($d['nombres']) ?>, quedaste inscrito en <strong><?= e($programa['nombre']) ?></strong>
         (<?= tipo_texto($programa['tipo']) ?>).</p>
      <p class="codigo">Código: <strong><?= e($codigo) ?></strong></p>
      <p class="alerta alerta--<?= $canal === 'fallido' ? 'error' : 'ok' ?>"><?= e($textosCanal[$canal]) ?></p>

      <div class="burbuja">
        <span class="burbuja__titulo">Mensaje enviado al +<?= e(numero_internacional($d['celular'])) ?></span>
        <p><?= e($mensaje) ?></p>
      </div>

      <a href="index.php" class="boton">Volver al inicio</a>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
