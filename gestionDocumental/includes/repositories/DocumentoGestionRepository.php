<?php
if (!class_exists('DocumentoGestionRepository')) {
    class DocumentoGestionRepository
    {
        /** @var wpdb */
        private $wpdb;
        private $tablaDocumentos;
        private $tablaDetalle;
        private $tablaTipos;
        private $tablaTiposContabilidad;
        private $tablaClientes;

        public function __construct(
            $wpdb,
            $tablaDocumentos = 'bc_documento_gestion',
            $tablaDetalle = 'bc_documento_gestion_detalle',
            $tablaTipos = 'bc_tipo_gestion_documental',
            $tablaTiposContabilidad = 'bc_tipo_documento_contabilidad',
            $tablaClientes = 'bc_cliente'
        ) {
            $this->wpdb = $wpdb;
            $this->tablaDocumentos = $tablaDocumentos;
            $this->tablaDetalle = $tablaDetalle;
            $this->tablaTipos = $tablaTipos;
            $this->tablaTiposContabilidad = $tablaTiposContabilidad;
            $this->tablaClientes = $tablaClientes;
        }

        public function contarPorGestion($idGestion, $searchTerm = '')
        {
            $sql = "SELECT COUNT(*)
                    FROM {$this->tablaDocumentos} d
                    LEFT JOIN {$this->tablaTipos} t ON t.Id = d.IdTipoGestion
                    WHERE d.IdGestion = %d";
            $params = [$idGestion];

            if ($searchTerm !== '') {
                $like = '%' . $this->wpdb->esc_like($searchTerm) . '%';
                $sql .= " AND (d.NombreArchivo LIKE %s OR t.Nombre LIKE %s)";
                $params[] = $like;
                $params[] = $like;
            }

            return (int) $this->wpdb->get_var($this->wpdb->prepare($sql, ...$params));
        }

        public function obtenerPorGestion($idGestion, $limit, $offset, $searchTerm = '')
        {
            $sql = "SELECT d.ID AS DocumentoId,
                           d.NombreArchivo,
                           d.RutaArchivo,
                           d.FechaSubida,
                           t.Nombre AS TipoNombre,
                           dd.IdTipoDocContabilidad,
                           tc.Descripcion AS TipoDocContabilidad,
                           dd.IdCliente,
                           c.RazonSocial AS NombreClienteProveedor,
                           c.NumeroDocumento,
                           dd.FechaDocumento,
                           dd.Descripcion
                    FROM {$this->tablaDocumentos} d
                    LEFT JOIN {$this->tablaDetalle} dd ON d.ID = dd.IdDocumentoGestion
                    LEFT JOIN {$this->tablaTipos} t ON t.Id = d.IdTipoGestion
                    LEFT JOIN {$this->tablaTiposContabilidad} tc ON dd.IdTipoDocContabilidad = tc.ID
                    LEFT JOIN {$this->tablaClientes} c ON dd.IdCliente = c.ID
                    WHERE d.IdGestion = %d";
            $params = [$idGestion];

            if ($searchTerm !== '') {
                $like = '%' . $this->wpdb->esc_like($searchTerm) . '%';
                $sql .= " AND (d.NombreArchivo LIKE %s OR t.Nombre LIKE %s OR tc.Descripcion LIKE %s OR c.RazonSocial LIKE %s)";
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }

            $sql .= " ORDER BY d.FechaSubida DESC LIMIT %d OFFSET %d";
            $params[] = $limit;
            $params[] = $offset;

            return $this->wpdb->get_results($this->wpdb->prepare($sql, ...$params));
        }

        public function crearDocumento(array $data)
        {
            $exito = $this->wpdb->insert($this->tablaDocumentos, $data);
            if (!$exito) {
                return false;
            }

            return (int) $this->wpdb->insert_id;
        }

        public function guardarDetalle($idDocumento, array $data)
        {
            $data['IdDocumentoGestion'] = $idDocumento;

            $vacios = true;
            foreach (['IdCliente', 'IdTipoDocContabilidad', 'FechaDocumento', 'Descripcion'] as $campo) {
                if (!empty($data[$campo])) {
                    $vacios = false;
                    break;
                }
            }

            if ($vacios) {
                return true;
            }

            return (bool) $this->wpdb->insert($this->tablaDetalle, $data);
        }
    }
}
