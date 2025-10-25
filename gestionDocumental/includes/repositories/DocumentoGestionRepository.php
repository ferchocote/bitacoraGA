<?php
// Repositorio para documentos de gestión documental
class DocumentoGestionRepository {
    private $wpdb;
    public function __construct($wpdb) {
        $this->wpdb = $wpdb;
    }
    // Obtiene documentos de una gestión, junto con el nombre del tipo
    public function getDocumentosPorGestion($idGestion) {
        $sql = "SELECT d.*, t.Nombre AS TipoNombre, det.Descripcion
                FROM bc_documento_gestion d
                INNER JOIN bc_tipo_gestion_documental t ON t.ID = d.IdTipoGestion
                LEFT JOIN bc_documento_gestion_detalle det ON det.IdDocumentoGestion = d.ID
                WHERE d.IdGestion = %d
                ORDER BY t.Nombre, d.FechaSubida DESC";
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $idGestion));
    }

    /**
     * Guarda un documento en la base de datos
     */
    public function crearDocumento($gestionId, $tipoDocumentoId, $nombre, $rutaArchivo, $usuarioId) {
        $res = $this->wpdb->insert('bc_gestion_documental_documento', [
            'IdGestionDocumental' => $gestionId,
            'IdTipoDocumento' => $tipoDocumentoId,
            'NombreArchivo' => $nombre,
            'RutaArchivo' => $rutaArchivo,
            'UsuarioCreador' => $usuarioId,
            'Activo' => 1,
            'FechaCreacion' => current_time('mysql'),
            'FechaActualizacion' => current_time('mysql')
        ]);
        return $res ? $this->wpdb->insert_id : false;
    }

    /**
     * Guarda un documento en la base de datos principal y su detalle
     */
    public function crearDocumentoGestion($dataDocumento, $dataDetalle) {
        $this->wpdb->insert('bc_documento_gestion', $dataDocumento);
        $idDocumento = $this->wpdb->insert_id;
        if (!$idDocumento) return false;
        $dataDetalle['IdDocumentoGestion'] = $idDocumento;
        $this->wpdb->insert('bc_documento_gestion_detalle', $dataDetalle);
        return $idDocumento;
    }
}
