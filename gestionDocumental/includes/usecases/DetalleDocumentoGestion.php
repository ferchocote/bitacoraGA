<?php
// Caso de uso: Obtener el detalle de un documento en una gestión documental
require_once __DIR__ . '/../repositories/GestionDocumentalRepository.php';
class DetalleDocumentoGestion {
    private $repo;
    public function __construct($wpdb) {
        $this->repo = new GestionDocumentalRepository($wpdb);
    }
    /**
     * Obtiene el detalle de un documento por su ID y gestión
     */
    public function ejecutar($gestionId, $documentoId) {
        $documentos = $this->repo->getDocumentosByGestionId($gestionId);
        foreach ($documentos as $doc) {
            if ($doc['Id'] == $documentoId) {
                return $doc;
            }
        }
        return null;
    }
}
