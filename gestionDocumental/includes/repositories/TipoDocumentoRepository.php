<?php
// Repositorio para tipos de documentos
class TipoDocumentoRepository {
    private $wpdb;
    public function __construct($wpdb) {
        $this->wpdb = $wpdb;
    }
    public function getTiposGestionDocumental() {       
        return $this->wpdb->get_results("SELECT Id, Nombre FROM bc_tipo_gestion_documental WHERE Activo=1 ORDER BY Nombre");
    }
    public function getTiposContabilidad() {
        return $this->wpdb->get_results("SELECT Id, Descripcion FROM bc_tipo_documento_contabilidad WHERE Activo=1 ORDER BY Descripcion");
    }
    public function getTiposClienteProveedor() {
        return $this->wpdb->get_results("SELECT Id, Descripcion FROM bc_tipo_documento WHERE Activo=1 ORDER BY Descripcion");
    }
}
