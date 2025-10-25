<?php
if (!class_exists('ImportadorRepository')) {
    class ImportadorRepository
    {
        /** @var wpdb */
        private $wpdb;
        private $tablaClientes;
        private $tablaGestiones;

        public function __construct($wpdb, $tablaClientes = 'bc_cliente', $tablaGestiones = 'bc_gestion_documental')
        {
            $this->wpdb = $wpdb;
            $this->tablaClientes = $tablaClientes;
            $this->tablaGestiones = $tablaGestiones;
        }

        /**
         * Obtiene todos los importadores registrados ordenados alfabéticamente.
         *
         * @return array
         */
        public function getTodosImportadores()
        {
            $sql = "SELECT ID, RazonSocial, NumeroDocumento FROM {$this->tablaClientes} ORDER BY RazonSocial ASC";
            return $this->wpdb->get_results($sql);
        }

        /**
         * Recupera las gestiones documentales con información del importador aplicando un filtro opcional.
         *
         * @param int    $limit
         * @param int    $offset
         * @param string $searchTerm
         * @return array
         */
        public function obtenerGestionesPaginadas($limit, $offset, $searchTerm = '')
        {
            $where = '';
            $params = [$limit, $offset];

            if ($searchTerm !== '') {
                $like = '%' . $this->wpdb->esc_like($searchTerm) . '%';
                $where = "WHERE c.RazonSocial LIKE %s OR c.NumeroDocumento LIKE %s";
                $params = [$like, $like, $limit, $offset];
            }

            $sql = "SELECT gd.*, gd.ID AS GestionID, c.RazonSocial, c.NumeroDocumento
                    FROM {$this->tablaGestiones} gd
                    LEFT JOIN {$this->tablaClientes} c ON c.ID = gd.IdImportador
                    {$where}
                    ORDER BY gd.ID DESC
                    LIMIT %d OFFSET %d";

            if ($where !== '') {
                return $this->wpdb->get_results($this->wpdb->prepare($sql, ...$params));
            }

            return $this->wpdb->get_results($this->wpdb->prepare($sql, $limit, $offset));
        }

        /**
         * Cuenta las gestiones registradas aplicando el mismo criterio de búsqueda que la lista paginada.
         *
         * @param string $searchTerm
         * @return int
         */
        public function contarGestiones($searchTerm = '')
        {
            $sql = "SELECT COUNT(*)
                    FROM {$this->tablaGestiones} gd
                    LEFT JOIN {$this->tablaClientes} c ON c.ID = gd.IdImportador";

            if ($searchTerm !== '') {
                $like = '%' . $this->wpdb->esc_like($searchTerm) . '%';
                $sql .= " WHERE c.RazonSocial LIKE %s OR c.NumeroDocumento LIKE %s";
                return (int) $this->wpdb->get_var($this->wpdb->prepare($sql, $like, $like));
            }

            return (int) $this->wpdb->get_var($sql);
        }

        /**
         * Obtiene un importador por su identificador.
         *
         * @param int $idImportador
         * @return object|null
         */
        public function obtenerImportador($idImportador)
        {
            return $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM {$this->tablaClientes} WHERE ID = %d",
                $idImportador
            ));
        }
    }
}
