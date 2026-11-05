<?php
/* EduControl - Ingreso del administrador */
require_once __DIR__ . '/../includes/db.php';
session_start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido($_POST['csrf'] ?? null)) {
        $error = 'La sesión expiró. Intenta de nuevo.';
    } else {
        $st = db()->prepare('SELECT * FROM administradores WHERE usuario = ?');
        $st->execute([trim($_POST['usuario'] ?? '')]);
        $admin = $st->fetch();
        if ($admin && password_verify($_POST['contrasena'] ?? '', $admin['contrasena_cifrada'])) {
            session_regenerate_id(true);
            $_SESSION['admin'] = $admin['usuario'];
            header('Location: index.php');
            exit;
        }
        $error = 'Usuario o contraseña incorrectos.';
    }
}

$titulo = 'Ingreso administrador';
$base = '../';
require __DIR__ . '/../includes/header.php';
?>
<section class="seccion">
  <div class="contenedor contenedor--mini">
    <form class="formulario formulario--una" method="post">
      <h1>Panel administrativo</h1>
      <?php if ($error): ?><p class="alerta alerta--error"><?= e($error) ?></p><?php endif; ?>
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <label>Usuario <input type="text" name="usuario" required autofocus></label>
      <label>Contraseña <input type="password" name="contrasena" required></label>
      <button class="boton boton--grande" type="submit">Ingresar</button>
    </form>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
