<?php
require_once __DIR__ . '/../repositories/DocumentoGestionRepository.php';

if (!class_exists('CrearDocumentoGestion')) {
    class CrearDocumentoGestion
    {
        private $repositorio;

        public function __construct($wpdb)
        {
            $this->repositorio = new DocumentoGestionRepository($wpdb);
        }

        public function ejecutar(array $dataDocumento, array $dataDetalle = [])
        {
            if (!isset($dataDocumento['FechaSubida'])) {
                $dataDocumento['FechaSubida'] = current_time('mysql');
            }

            if (!isset($dataDocumento['UsuarioCreador'])) {
                $dataDocumento['UsuarioCreador'] = get_current_user_id();
            }

            $idDocumento = $this->repositorio->crearDocumento($dataDocumento);
            if (!$idDocumento) {
                return false;
            }

            if (!empty($dataDetalle)) {
                $this->repositorio->guardarDetalle($idDocumento, $dataDetalle);
            }

            return $idDocumento;
        }
    }
}
