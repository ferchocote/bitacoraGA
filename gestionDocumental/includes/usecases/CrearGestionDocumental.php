<?php
// Caso de uso: Crear una nueva gestión documental
require_once __DIR__ . '/../repositories/GestionDocumentalRepository.php';

function crear_gestion_documental($wpdb, $idImportador, $idUsuario) {
    $repo = new GestionDocumentalRepository($wpdb);
    return $repo->crearGestion($idImportador, $idUsuario);
}
