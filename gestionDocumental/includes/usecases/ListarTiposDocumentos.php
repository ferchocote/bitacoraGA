<?php
if (!class_exists('ListarTiposDocumentos')) {
    class ListarTiposDocumentos
    {
        /** @var wpdb */
        private $wpdb;

        public function __construct($wpdb)
        {
            $this->wpdb = $wpdb;
        }

        private function tieneColumna($tabla, $columna)
        {
            $resultado = $this->wpdb->get_results($this->wpdb->prepare(
                "SHOW COLUMNS FROM {$tabla} LIKE %s",
                $columna
            ));

            return !empty($resultado);
        }

        public function obtenerTipos()
        {
            $tablaTipos = 'bc_tipo_gestion_documental';
            $tablaContabilidad = 'bc_tipo_documento_contabilidad';
            $tablaClientes = 'bc_cliente';

            $condicionActivos = '';
            if ($this->tieneColumna($tablaTipos, 'Activo')) {
                $condicionActivos = 'WHERE Activo = 1';
            }

            $tiposDocumento = $this->wpdb->get_results(
                "SELECT Id, Nombre FROM {$tablaTipos} {$condicionActivos} ORDER BY Nombre ASC"
            );

            $tiposDocContabilidad = $this->wpdb->get_results(
                "SELECT ID, Descripcion FROM {$tablaContabilidad} ORDER BY Descripcion ASC"
            );

            $tiposDocCliente = $this->wpdb->get_results(
                "SELECT ID, RazonSocial, NumeroDocumento FROM {$tablaClientes} ORDER BY RazonSocial ASC"
            );

            return [
                'tiposDocumento'       => $tiposDocumento,
                'tiposDocContabilidad' => $tiposDocContabilidad,
                'tiposDocCliente'      => $tiposDocCliente,
            ];
        }
    }
}
