<?php
require_once __DIR__ . '/../../../bitacoras/includes/suscripcion.php';
// Repositorio para operaciones sobre gestiones documentales
class GestionDocumentalRepository {
    private $wpdb;
    public function __construct($wpdb) {
        $this->wpdb = $wpdb;
    }
    public function crearGestion($idImportador, $idUsuario) {
        if (bc_suscripcion_esta_bloqueada($this->wpdb)) {
            return false;
        }

        $res = $this->wpdb->insert('bc_gestion_documental', [
            'IdImportador' => $idImportador,
            'UsuarioCreador' => $idUsuario,
            'Activo' => 1,
            'FechaCreacion' => current_time('mysql'),
            'FechaActualizacion' => current_time('mysql')
        ]);
        return $res ? $this->wpdb->insert_id : false;
    }

    /**
     * Retorna true si existe una gestión activa para el importador
     */
    public function existeGestionActiva($idImportador) {
        $sql = "SELECT COUNT(*) FROM bc_gestion_documental WHERE IdImportador = %d AND Activo = 1";
        $count = $this->wpdb->get_var($this->wpdb->prepare($sql, $idImportador));
        return $count > 0;
    }

    /**
     * Obtiene los documentos de una gestión, incluyendo descripciones de contabilidad y cliente/proveedor
     */
    public function getDocumentosByGestionId($gestionId) {
        $sql = "SELECT dg.ID as DocumentoId,
                       dg.NombreArchivo,
                       dg.RutaArchivo,
                       dg.FechaSubida,
                       tgd.Nombre AS TipoGestionDocumental,
                       dgd.IdTipoDocContabilidad,
                       tc.Descripcion AS TipoDocContabilidad,
                       dgd.IdTipoDocCliente,
                       tcli.Descripcion AS TipoDocCliente,
                       dgd.NombreClienteProveedor,
                       dgd.FechaDocumento,
                       dgd.Descripcion
                FROM bc_documento_gestion dg
                LEFT JOIN bc_documento_gestion_detalle dgd ON dg.ID = dgd.IdDocumentoGestion
                LEFT JOIN bc_tipo_gestion_documental tgd ON dg.IdTipoGestion = tgd.ID
                LEFT JOIN bc_tipo_documento_contabilidad tc ON dgd.IdTipoDocContabilidad = tc.ID
                LEFT JOIN bc_tipo_documento tcli ON dgd.IdTipoDocCliente = tcli.ID
                WHERE dg.IdGestion = %d";
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $gestionId), ARRAY_A);
    }
}
