<?php
// Caso de uso: Listar importadores con gestión documental
require_once __DIR__ . '/../repositories/ImportadorRepository.php';

function listar_importadores_con_gestion($wpdb, $q = '') {
    $repo = new ImportadorRepository($wpdb);
    if ($q !== '') {
        return $repo->getImportadoresConGestionFiltrado($q);
    }
    return $repo->getImportadoresConGestion();
}
