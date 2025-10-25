<?php
if (!class_exists('ValidarGestionDocumentalDuplicada')) {
    class ValidarGestionDocumentalDuplicada
    {
        /** @var wpdb */
        private $wpdb;
        private $tabla;
        private $columnaEstado;
        private $columnaActivo;

        public function __construct($wpdb, $tabla = 'bc_gestion_documental')
        {
            $this->wpdb = $wpdb;
            $this->tabla = $tabla;
        }

        private function tieneColumna($nombre)
        {
            $resultado = $this->wpdb->get_results($this->wpdb->prepare(
                "SHOW COLUMNS FROM {$this->tabla} LIKE %s",
                $nombre
            ));

            return !empty($resultado);
        }

        private function columnaEstado()
        {
            if ($this->columnaEstado === null) {
                $this->columnaEstado = $this->tieneColumna('Estado');
            }

            return $this->columnaEstado;
        }

        private function columnaActivo()
        {
            if ($this->columnaActivo === null) {
                $this->columnaActivo = $this->tieneColumna('Activo');
            }

            return $this->columnaActivo;
        }

        public function existeGestionActiva($idImportador)
        {
            $condiciones = ['IdImportador = %d'];
            $params = [$idImportador];

            if ($this->columnaActivo()) {
                $condiciones[] = 'Activo = 1';
            } elseif ($this->columnaEstado()) {
                $condiciones[] = "(Estado IS NULL OR Estado NOT IN ('CERRADO', 'ANULADO'))";
            }

            $sql = "SELECT COUNT(*) FROM {$this->tabla} WHERE " . implode(' AND ', $condiciones);

            return (int) $this->wpdb->get_var($this->wpdb->prepare($sql, ...$params)) > 0;
        }
    }
}
