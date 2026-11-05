<?php
/* EduControl - Página principal: oferta + formulario de inscripción */
require_once __DIR__ . '/includes/db.php';

$sql = "SELECT p.*, (p.cupos - COUNT(i.id_inscripcion)) AS disponibles
        FROM programas p
        LEFT JOIN inscripciones i ON i.id_programa = p.id_programa
        WHERE p.estado = 1
        GROUP BY p.id_programa, p.nombre, p.tipo, p.descripcion, p.jornada, p.duracion, p.cupos, p.estado
        ORDER BY p.tipo, p.nombre";
$programas = db()->query($sql)->fetchAll();

// Si viene desde una tarjeta, el programa queda preseleccionado
$seleccionado = (int)($_GET['programa'] ?? 0);

// Si inscribir.php devolvió errores, se recuperan los datos escritos
session_start();
$errores = $_SESSION['errores'] ?? [];
$viejo   = $_SESSION['viejo'] ?? [];
unset($_SESSION['errores'], $_SESSION['viejo']);
if (!$seleccionado && !empty($viejo['id_programa'])) $seleccionado = (int)$viejo['id_programa'];

$titulo = 'Inscripciones';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="contenedor hero__fila">
    <div>
      <h1>Inscríbete en línea a cursos, técnicas y auxiliares</h1>
      <p>Elige tu programa, diligencia el formulario y recibe la confirmación de tu inscripción
         directamente en tu <strong>WhatsApp</strong> o celular.</p>
      <a href="#oferta" class="boton boton--claro">Ver la oferta</a>
    </div>
    <ol class="pasos">
      <li><span>1</span> Elige el programa</li>
      <li><span>2</span> Completa tus datos</li>
      <li><span>3</span> Recibe la confirmación en tu WhatsApp</li>
    </ol>
  </div>
</section>

<section id="oferta" class="seccion">
  <div class="contenedor">
    <h2>Oferta disponible</h2>
    <div class="filtros" role="tablist">
      <button class="filtro activo" data-filtro="todos">Todos</button>
      <button class="filtro" data-filtro="curso">Cursos</button>
      <button class="filtro" data-filtro="tecnica">Técnicas</button>
      <button class="filtro" data-filtro="auxiliar">Auxiliares</button>
    </div>

    <div class="tarjetas">
      <?php foreach ($programas as $p): $lleno = $p['disponibles'] <= 0; ?>
        <article class="tarjeta" data-tipo="<?= e($p['tipo']) ?>">
          <span class="etiqueta etiqueta--<?= e($p['tipo']) ?>"><?= tipo_texto($p['tipo']) ?></span>
          <h3><?= e($p['nombre']) ?></h3>
          <p><?= e($p['descripcion']) ?></p>
          <ul class="datos">
            <li>Jornada: <?= e($p['jornada']) ?></li>
            <?php if ($p['duracion']): ?><li>Duración: <?= e($p['duracion']) ?></li><?php endif; ?>
            <li>Cupos disponibles: <strong><?= max(0, (int)$p['disponibles']) ?></strong></li>
          </ul>
          <?php if ($lleno): ?>
            <span class="boton boton--deshabilitado">Sin cupos</span>
          <?php else: ?>
            <a class="boton" href="?programa=<?= (int)$p['id_programa'] ?>#inscripcion"
               data-programa="<?= (int)$p['id_programa'] ?>">Inscribirme</a>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
      <?php if (!$programas): ?><p>Por ahora no hay programas abiertos.</p><?php endif; ?>
    </div>
  </div>
</section>

<section id="inscripcion" class="seccion seccion--gris">
  <div class="contenedor contenedor--angosto">
    <h2>Formulario de inscripción</h2>

    <?php if ($errores): ?>
      <div class="alerta alerta--error">
        <strong>Revisa lo siguiente:</strong>
        <ul><?php foreach ($errores as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <form id="formInscripcion" class="formulario" action="inscribir.php" method="post" novalidate>
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

      <label class="completo">Programa *
        <select name="id_programa" required>
          <option value="">Seleccione un programa</option>
          <?php foreach (['curso' => 'Cursos', 'tecnica' => 'Técnicas', 'auxiliar' => 'Auxiliares'] as $tipo => $grupo): ?>
            <optgroup label="<?= $grupo ?>">
              <?php foreach ($programas as $p): if ($p['tipo'] !== $tipo || $p['disponibles'] <= 0) continue; ?>
                <option value="<?= (int)$p['id_programa'] ?>" <?= $seleccionado === (int)$p['id_programa'] ? 'selected' : '' ?>>
                  <?= e($p['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select>
      </label>

      <label>Tipo de documento *
        <select name="tipo_documento" required>
          <?php foreach (['CC' => 'Cédula de ciudadanía', 'TI' => 'Tarjeta de identidad',
                          'CE' => 'Cédula de extranjería', 'PPT' => 'Permiso por Protección Temporal'] as $v => $t): ?>
            <option value="<?= $v ?>" <?= ($viejo['tipo_documento'] ?? 'CC') === $v ? 'selected' : '' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>Número de documento *
        <input type="text" name="documento" inputmode="numeric" maxlength="15" required
               value="<?= e($viejo['documento'] ?? '') ?>" placeholder="Ej. 1091356579">
      </label>

      <label>Nombres *
        <input type="text" name="nombres" maxlength="80" required value="<?= e($viejo['nombres'] ?? '') ?>">
      </label>

      <label>Apellidos *
        <input type="text" name="apellidos" maxlength="80" required value="<?= e($viejo['apellidos'] ?? '') ?>">
      </label>

      <label>Celular / WhatsApp *
        <input type="tel" name="celular" inputmode="numeric" maxlength="10" required
               value="<?= e($viejo['celular'] ?? '') ?>" placeholder="Ej. 3001234567">
        <small>Aquí te llegará la confirmación de tu inscripción.</small>
      </label>

      <label>Correo electrónico *
        <input type="email" name="correo" maxlength="120" required value="<?= e($viejo['correo'] ?? '') ?>">
      </label>

      <label class="completo casilla">
        <input type="checkbox" name="autoriza_datos" value="1" required <?= !empty($viejo['autoriza_datos']) ? 'checked' : '' ?>>
        <span>Autorizo al <?= e(INSTITUCION) ?> para el tratamiento de mis datos personales con el fin de
          gestionar mi inscripción, de acuerdo con la Ley 1581 de 2012. *</span>
      </label>

      <div class="completo">
        <button type="submit" class="boton boton--grande">Enviar inscripción</button>
      </div>
    </form>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
