<?php
// Caso de uso: Validar si existe una gestión documental activa para un importador
require_once __DIR__ . '/../repositories/GestionDocumentalRepository.php';
class ValidarGestionDocumentalDuplicada {
    private $repo;
    public function __construct($wpdb) {
        $this->repo = new GestionDocumentalRepository($wpdb);
    }
    /**
     * Retorna true si existe una gestión activa para el importador
     */
    public function existeGestionActiva($idImportador) {
        return $this->repo->existeGestionActiva($idImportador);
    }
}
