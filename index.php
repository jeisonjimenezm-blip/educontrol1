<?php
/* EduControl - Panel: listado de inscritos por programa */
require __DIR__ . '/auth.php';
$pdo = db();

$filtroPrograma = (int)($_GET['programa'] ?? 0);
$filtroTipo     = $_GET['tipo'] ?? '';
$buscar         = trim($_GET['buscar'] ?? '');

// Totales para las tarjetas de resumen
$totales = ['curso' => 0, 'tecnica' => 0, 'auxiliar' => 0];
foreach ($pdo->query('SELECT p.tipo, COUNT(*) AS n FROM inscripciones i JOIN programas p ON p.id_programa = i.id_programa GROUP BY p.tipo') as $r) {
    $totales[$r['tipo']] = (int)$r['n'];
}

$programas = $pdo->query('SELECT id_programa, nombre, tipo FROM programas ORDER BY tipo, nombre')->fetchAll();

// Consulta de inscritos con filtros
$sql = "SELECT i.codigo, i.fecha, i.estado_confirmacion, a.tipo_documento, a.documento, a.nombres, a.apellidos,
               a.celular, a.correo, p.nombre AS programa, p.tipo
        FROM inscripciones i
        JOIN aspirantes a ON a.id_aspirante = i.id_aspirante
        JOIN programas p  ON p.id_programa  = i.id_programa
        WHERE 1 = 1";
$params = [];
if ($filtroPrograma) { $sql .= ' AND p.id_programa = ?'; $params[] = $filtroPrograma; }
if (in_array($filtroTipo, ['curso', 'tecnica', 'auxiliar'], true)) { $sql .= ' AND p.tipo = ?'; $params[] = $filtroTipo; }
if ($buscar !== '') {
    $sql .= ' AND (a.documento LIKE ? OR a.nombres LIKE ? OR a.apellidos LIKE ? OR i.codigo LIKE ?)';
    array_push($params, "%$buscar%", "%$buscar%", "%$buscar%", "%$buscar%");
}
$sql .= ' ORDER BY i.fecha DESC';
$st = $pdo->prepare($sql);
$st->execute($params);
$inscritos = $st->fetchAll();

// Exportar a CSV (se abre en Excel)
if (isset($_GET['exportar'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="inscritos_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM para que Excel lea las tildes
    fputcsv($out, ['Código', 'Fecha', 'Tipo doc.', 'Documento', 'Nombres', 'Apellidos', 'Celular', 'Correo', 'Programa', 'Tipo', 'Confirmación'], ';');
    foreach ($inscritos as $r) {
        fputcsv($out, [$r['codigo'], $r['fecha'], $r['tipo_documento'], $r['documento'], $r['nombres'], $r['apellidos'],
                       $r['celular'], $r['correo'], $r['programa'], tipo_texto($r['tipo']), $r['estado_confirmacion']], ';');
    }
    exit;
}

$titulo = 'Panel de inscritos';
require __DIR__ . '/../includes/header.php';
?>
<section class="seccion">
  <div class="contenedor">
    <div class="panel__cabecera">
      <h1>Inscritos</h1>
      <div>
        <a class="boton boton--borde" href="programas.php">Gestionar programas</a>
        <a class="boton boton--borde" href="logout.php">Cerrar sesión (<?= e($_SESSION['admin']) ?>)</a>
      </div>
    </div>

    <div class="resumen">
      <div><span><?= array_sum($totales) ?></span>Total inscritos</div>
      <div><span><?= $totales['curso'] ?></span>Cursos</div>
      <div><span><?= $totales['tecnica'] ?></span>Técnicas</div>
      <div><span><?= $totales['auxiliar'] ?></span>Auxiliares</div>
    </div>

    <form class="filtros-panel" method="get">
      <select name="tipo">
        <option value="">Todos los tipos</option>
        <?php foreach (['curso', 'tecnica', 'auxiliar'] as $t): ?>
          <option value="<?= $t ?>" <?= $filtroTipo === $t ? 'selected' : '' ?>><?= tipo_texto($t) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="programa">
        <option value="0">Todos los programas</option>
        <?php foreach ($programas as $p): ?>
          <option value="<?= $p['id_programa'] ?>" <?= $filtroPrograma === (int)$p['id_programa'] ? 'selected' : '' ?>><?= e($p['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="search" name="buscar" value="<?= e($buscar) ?>" placeholder="Documento, nombre o código">
      <button class="boton" type="submit">Filtrar</button>
      <button class="boton boton--borde" type="submit" name="exportar" value="1">Exportar a Excel (CSV)</button>
    </form>

    <div class="tabla-scroll">
      <table class="tabla">
        <thead>
          <tr><th>Código</th><th>Fecha</th><th>Documento</th><th>Nombre</th><th>Celular</th><th>Correo</th><th>Programa</th><th>Confirmación</th></tr>
        </thead>
        <tbody>
          <?php foreach ($inscritos as $r): ?>
            <tr>
              <td><?= e($r['codigo']) ?></td>
              <td><?= e(date('d/m/Y H:i', strtotime($r['fecha']))) ?></td>
              <td><?= e($r['tipo_documento'] . ' ' . $r['documento']) ?></td>
              <td><?= e($r['nombres'] . ' ' . $r['apellidos']) ?></td>
              <td><?= e($r['celular']) ?></td>
              <td><?= e($r['correo']) ?></td>
              <td><span class="etiqueta etiqueta--<?= e($r['tipo']) ?>"><?= tipo_texto($r['tipo']) ?></span> <?= e($r['programa']) ?></td>
              <td><span class="estado estado--<?= e($r['estado_confirmacion']) ?>"><?= e(ucfirst($r['estado_confirmacion'])) ?></span></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$inscritos): ?><tr><td colspan="8" class="vacio">No hay inscritos con estos filtros.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
