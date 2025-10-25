<?php
// Caso de uso: Crear un nuevo documento en una gestión documental
require_once __DIR__ . '/../repositories/DocumentoGestionRepository.php';
class CrearDocumentoGestion {
    private $repo;
    public function __construct($wpdb) {
        $this->repo = new DocumentoGestionRepository($wpdb);
    }
    /**
     * Crea un documento y su detalle, retorna el ID o false
     */
    public function ejecutar($dataDocumento, $dataDetalle) {
        return $this->repo->crearDocumentoGestion($dataDocumento, $dataDetalle);
    }
}
