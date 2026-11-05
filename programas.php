<?php
/* EduControl - Panel: crear, editar y activar/desactivar programas */
require __DIR__ . '/auth.php';
$pdo = db();
$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido($_POST['csrf'] ?? null)) {
        $error = 'La sesión expiró. Intenta de nuevo.';
    } elseif (($_POST['accion'] ?? '') === 'estado') {
        $pdo->prepare('UPDATE programas SET estado = 1 - estado WHERE id_programa = ?')->execute([(int)$_POST['id']]);
        $mensaje = 'Estado del programa actualizado.';
    } else {
        $p = [
            'nombre'      => trim($_POST['nombre'] ?? ''),
            'tipo'        => $_POST['tipo'] ?? '',
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'jornada'     => trim($_POST['jornada'] ?? 'Tarde'),
            'duracion'    => trim($_POST['duracion'] ?? ''),
            'cupos'       => max(1, (int)($_POST['cupos'] ?? 30)),
        ];
        if ($p['nombre'] === '' || !in_array($p['tipo'], ['curso', 'tecnica', 'auxiliar'], true)) {
            $error = 'El nombre y el tipo son obligatorios.';
        } elseif (!empty($_POST['id'])) {
            $pdo->prepare('UPDATE programas SET nombre=?, tipo=?, descripcion=?, jornada=?, duracion=?, cupos=? WHERE id_programa=?')
                ->execute([...array_values($p), (int)$_POST['id']]);
            $mensaje = 'Programa actualizado.';
        } else {
            $pdo->prepare('INSERT INTO programas (nombre, tipo, descripcion, jornada, duracion, cupos) VALUES (?,?,?,?,?,?)')
                ->execute(array_values($p));
            $mensaje = 'Programa creado.';
        }
    }
}

// Programa a editar (si se eligió uno)
$editar = null;
if (!empty($_GET['editar'])) {
    $st = $pdo->prepare('SELECT * FROM programas WHERE id_programa = ?');
    $st->execute([(int)$_GET['editar']]);
    $editar = $st->fetch() ?: null;
}

$programas = $pdo->query('SELECT p.*, (SELECT COUNT(*) FROM inscripciones i WHERE i.id_programa = p.id_programa) AS inscritos
                          FROM programas p ORDER BY p.tipo, p.nombre')->fetchAll();

$titulo = 'Programas';
require __DIR__ . '/../includes/header.php';
?>
<section class="seccion">
  <div class="contenedor">
    <div class="panel__cabecera">
      <h1>Programas de la oferta</h1>
      <a class="boton boton--borde" href="index.php">&larr; Volver a inscritos</a>
    </div>
    <?php if ($mensaje): ?><p class="alerta alerta--ok"><?= e($mensaje) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="alerta alerta--error"><?= e($error) ?></p><?php endif; ?>

    <form class="formulario" method="post">
      <h2 class="completo"><?= $editar ? 'Editar programa' : 'Nuevo programa' ?></h2>
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="id" value="<?= (int)($editar['id_programa'] ?? 0) ?: '' ?>">
      <label class="completo">Nombre * <input type="text" name="nombre" required maxlength="150" value="<?= e($editar['nombre'] ?? '') ?>"></label>
      <label>Tipo *
        <select name="tipo" required>
          <?php foreach (['curso', 'tecnica', 'auxiliar'] as $t): ?>
            <option value="<?= $t ?>" <?= ($editar['tipo'] ?? '') === $t ? 'selected' : '' ?>><?= tipo_texto($t) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Jornada <input type="text" name="jornada" value="<?= e($editar['jornada'] ?? 'Tarde') ?>"></label>
      <label>Duración <input type="text" name="duracion" value="<?= e($editar['duracion'] ?? '') ?>" placeholder="Ej. 40 horas"></label>
      <label>Cupos <input type="number" name="cupos" min="1" value="<?= (int)($editar['cupos'] ?? 30) ?>"></label>
      <label class="completo">Descripción <textarea name="descripcion" rows="2" maxlength="500"><?= e($editar['descripcion'] ?? '') ?></textarea></label>
      <div class="completo">
        <button class="boton" type="submit"><?= $editar ? 'Guardar cambios' : 'Crear programa' ?></button>
        <?php if ($editar): ?><a class="boton boton--borde" href="programas.php">Cancelar</a><?php endif; ?>
      </div>
    </form>

    <div class="tabla-scroll">
      <table class="tabla">
        <thead><tr><th>Programa</th><th>Tipo</th><th>Jornada</th><th>Inscritos / Cupos</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($programas as $p): ?>
          <tr>
            <td><?= e($p['nombre']) ?></td>
            <td><span class="etiqueta etiqueta--<?= e($p['tipo']) ?>"><?= tipo_texto($p['tipo']) ?></span></td>
            <td><?= e($p['jornada']) ?></td>
            <td><?= (int)$p['inscritos'] ?> / <?= (int)$p['cupos'] ?></td>
            <td><span class="estado estado--<?= $p['estado'] ? 'whatsapp' : 'fallido' ?>"><?= $p['estado'] ? 'Activo' : 'Inactivo' ?></span></td>
            <td class="acciones">
              <a href="?editar=<?= (int)$p['id_programa'] ?>">Editar</a>
              <form method="post">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="accion" value="estado">
                <input type="hidden" name="id" value="<?= (int)$p['id_programa'] ?>">
                <button type="submit" class="enlace"><?= $p['estado'] ? 'Desactivar' : 'Activar' ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
