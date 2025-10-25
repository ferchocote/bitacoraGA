<?php
// Caso de uso: Listar tipos de documentos para los selects
require_once __DIR__ . '/../repositories/TipoDocumentoRepository.php';
class ListarTiposDocumentos {
    private $repo;
    public function __construct($wpdb) {
        $this->repo = new TipoDocumentoRepository($wpdb);
    }
    public function obtenerTipos() {
        return [
            'tiposDocumento' => $this->repo->getTiposGestionDocumental(),
            'tiposDocContabilidad' => $this->repo->getTiposContabilidad(),
            'tiposDocCliente' => $this->repo->getTiposClienteProveedor(),
        ];
    }
}
