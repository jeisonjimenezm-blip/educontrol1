<?php
/* Protege las páginas del panel: si no hay sesión, envía al login */
require_once __DIR__ . '/../includes/db.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
$base = '../';
