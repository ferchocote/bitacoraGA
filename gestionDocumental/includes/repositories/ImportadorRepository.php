<?php
// Repositorio para operaciones sobre importadores y sus gestiones documentales
class ImportadorRepository {
    private $wpdb;
    public function __construct($wpdb) {
        $this->wpdb = $wpdb;
    }
    // Devuelve importadores con gestión documental
    public function getImportadoresConGestion() {
        $sql = "SELECT i.ID, i.RazonSocial, i.NumeroDocumento, gd.ID AS GestionID
                FROM bc_cliente i
                INNER JOIN bc_gestion_documental gd ON gd.IdImportador = i.ID";
        return $this->wpdb->get_results($sql);
    }

    // Devuelve todos los importadores
    public function getTodosImportadores() {
        $sql = "SELECT ID, RazonSocial,NumeroDocumento FROM bc_cliente WHERE EsCliente = 0 AND Activo = 1 ORDER BY RazonSocial";
        return $this->wpdb->get_results($sql);
    }

    // Devuelve importadores con gestión documental, filtrando por búsqueda
    public function getImportadoresConGestionFiltrado($q = '') {
        $where = '';
        $params = [];
        if ($q !== '') {
            $where = "WHERE (i.RazonSocial LIKE %s OR i.NumeroDocumento LIKE %s)";
            $like = '%' . $this->wpdb->esc_like($q) . '%';
            $params = [$like, $like];
        }
        $sql = "SELECT i.ID, i.RazonSocial, i.NumeroDocumento, gd.ID AS GestionID
                FROM bc_cliente i
                INNER JOIN bc_gestion_documental gd ON gd.IdImportador = i.ID
                $where";
        return !empty($params) ? $this->wpdb->get_results($this->wpdb->prepare($sql, ...$params)) : $this->wpdb->get_results($sql);
    }

    // Devuelve todos los importadores, filtrando por búsqueda
    public function getTodosImportadoresFiltrado($q = '') {
        $where = '';
        $params = [];
        if ($q !== '') {
            $where = "AND (RazonSocial LIKE %s OR NumeroDocumento LIKE %s)";
            $like = '%' . $this->wpdb->esc_like($q) . '%';
            $params = [$like, $like];
        }
        $sql = "SELECT ID, RazonSocial, NumeroDocumento FROM bc_cliente WHERE EsCliente = 0 AND Activo = 1 $where ORDER BY RazonSocial";
        return !empty($params) ? $this->wpdb->get_results($this->wpdb->prepare($sql, ...$params)) : $this->wpdb->get_results($sql);
    }
}
