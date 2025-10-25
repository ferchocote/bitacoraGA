<?php
require_once __DIR__ . '/../includes/repositories/GestionDocumentalRepository.php';

$repo = new GestionDocumentalRepository($wpdb);
$documentos = $repo->getDocumentosByGestionId($gestionId);

// Agrupar y preparar los datos para la vista
$documentosAgrupados = [];
foreach ($documentos as $doc) {
    $tipo = $doc['TipoDocumento'];
    $fecha = $doc['FechaDocumento'];
    if (!isset($documentosAgrupados[$tipo])) {
        $documentosAgrupados[$tipo] = [];
    }
    if (!isset($documentosAgrupados[$tipo][$fecha])) {
        $documentosAgrupados[$tipo][$fecha] = [];
    }
    $documentosAgrupados[$tipo][$fecha][] = (object) $doc;
}