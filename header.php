<?php
/* Encabezado común. Antes de incluirlo defina $titulo y, si está dentro de /admin, $base = '../' */
$base = $base ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($titulo ?? 'EduControl') ?> | EduControl</title>
  <link rel="stylesheet" href="<?= $base ?>assets/css/styles.css">
</head>
<body>
<header class="barra">
  <div class="contenedor barra__fila">
    <a class="marca" href="<?= $base ?>index.php">
      <span class="marca__logo">EC</span>
      <span>EduControl<small><?= e(INSTITUCION) ?></small></span>
    </a>
    <nav class="menu">
      <a href="<?= $base ?>index.php#oferta">Oferta</a>
      <a href="<?= $base ?>index.php#inscripcion">Inscribirme</a>
      <a href="<?= $base ?>admin/index.php" class="menu__admin">Administrador</a>
    </nav>
  </div>
</header>
<main>
