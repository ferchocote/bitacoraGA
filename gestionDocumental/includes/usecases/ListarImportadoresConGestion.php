<?php
require_once __DIR__ . '/../repositories/ImportadorRepository.php';

if (!class_exists('ListarImportadoresConGestion')) {
    class ListarImportadoresConGestion
    {
        private $repositorio;

        public function __construct($wpdb)
        {
            $this->repositorio = new ImportadorRepository($wpdb);
        }

        public function ejecutar($perPage, $offset, $searchTerm = '')
        {
            return $this->repositorio->obtenerGestionesPaginadas($perPage, $offset, $searchTerm);
        }

        public function contar($searchTerm = '')
        {
            return $this->repositorio->contarGestiones($searchTerm);
        }
    }
}
