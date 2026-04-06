<?php
require_once __DIR__ . '/../../../wp-load.php';
require_once __DIR__ . '/../../bitacoras/includes/suscripcion.php';
require_once __DIR__ . '/../includes/usecases/DescargarArchivoGoogleDrive.php';

global $wpdb;

if (bc_suscripcion_esta_bloqueada($wpdb)) {
    bc_suscripcion_render_bloqueo_y_salir('Descarga deshabilitada');
}

if (!isset($_GET['id'])) {
    die('ID de archivo no especificado.');
}

$fileId = $_GET['id'];
$descargador = new DescargarArchivoGoogleDrive();
$result = $descargador->descargar($fileId);

if (isset($result['error'])) {
    echo $result['error'];
    exit;
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $result['name'] . '"');
echo $result['content'];
exit;
