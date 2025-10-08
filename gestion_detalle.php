<?php
// Controlador para el detalle de gestión documental
require_once __DIR__ . '/../gestionDocumental/includes/repositories/DocumentoGestionRepository.php';
require_once __DIR__ . '/../gestionDocumental/includes/usecases/ListarTiposDocumentos.php';
require_once __DIR__ . '/../gestionDocumental/includes/usecases/CrearDocumentoGestion.php';
require_once __DIR__ . '/../gestionDocumental/includes/usecases/SubirArchivoGoogleDrive.php';

global $wpdb;

$idGestion = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($idGestion <= 0) {
    echo "Gestión no válida.";
    exit;
}

$repoDoc = new DocumentoGestionRepository($wpdb);

$per_page = 10;
$page = max(1, intval($_GET['paged'] ?? 1));
$offset = ($page - 1) * $per_page;

// Total de documentos
$total_docs = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM bc_documento_gestion WHERE IdGestion = %d", $idGestion
));

// Documentos paginados
if ($q !== '') {
    $documentos = $wpdb->get_results($wpdb->prepare(
        "SELECT d.*, t.Nombre AS TipoNombre
         FROM bc_documento_gestion d
         LEFT JOIN bc_tipo_gestion_documental t ON t.Id = d.IdTipoGestion
         WHERE d.IdGestion = %d AND (d.NombreArchivo LIKE %s OR t.Nombre LIKE %s)
         ORDER BY d.FechaSubida DESC
         LIMIT %d OFFSET %d",
        $idGestion, "%$q%", "%$q%", $per_page, $offset
    ));
} else {
    $documentos = $wpdb->get_results($wpdb->prepare(
        "SELECT d.ID as DocumentoId,
                d.NombreArchivo,
                d.RutaArchivo,
                d.FechaSubida,
                t.Nombre AS TipoNombre,
                dd.IdTipoDocContabilidad,
                tc.Descripcion AS TipoDocContabilidad,
                dd.IdTipoDocCliente,
                tcli.Descripcion AS TipoDocCliente,
                dd.NombreClienteProveedor,
                dd.FechaDocumento,
                dd.Descripcion
         FROM bc_documento_gestion d
         LEFT JOIN bc_documento_gestion_detalle dd ON d.ID = dd.IdDocumentoGestion
         LEFT JOIN bc_tipo_gestion_documental t ON t.Id = d.IdTipoGestion
         LEFT JOIN bc_tipo_documento_contabilidad tc ON dd.IdTipoDocContabilidad = tc.ID
         LEFT JOIN bc_tipo_documento tcli ON dd.IdTipoDocCliente = tcli.ID
         WHERE d.IdGestion = %d
         ORDER BY d.FechaSubida DESC
         LIMIT %d OFFSET %d",
        $idGestion, $per_page, $offset
    ));
}

// Agrupar documentos paginados
$documentosAgrupados = [];
foreach ($documentos as $doc) {
    $tipo = $doc->TipoNombre;
    $fecha = date('Y-m-d', strtotime($doc->FechaSubida));
    $documentosAgrupados[$tipo][$fecha][] = $doc;
}

// Obtener tipos para los selects del popup
$listarTipos = new ListarTiposDocumentos($wpdb);
$tiposData = $listarTipos->obtenerTipos();
$tiposDocumento = $tiposData['tiposDocumento'];
$tiposDocContabilidad = $tiposData['tiposDocContabilidad'];
$tiposDocCliente = $tiposData['tiposDocCliente'];

$gestionId = $idGestion;

// Validar que la gestión existe antes de guardar
$gestionExiste = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM bc_gestion_documental WHERE ID = %d', $idGestion));
// Manejo de guardado de nuevo documento (sin archivo)
$mensaje = '';

// Obtener nombre del importador para la gestión
$nombreImportador = $wpdb->get_var($wpdb->prepare('
    SELECT c.RazonSocial
    FROM bc_gestion_documental gd
    INNER JOIN bc_cliente c ON c.ID = gd.IdImportador
    WHERE gd.ID = %d', $idGestion));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gestion_id'], $_POST['tipo_documento'], $_POST['nombre'])) {
    if (!$gestionExiste) {
        $mensaje = '<div class="notice error">No se puede guardar: la gestión seleccionada no existe. ID recibido: ' . esc_html($_POST['gestion_id']) . '</div>';
    } else {
        $rutaArchivo = '';
        // Subir archivo a Google Drive si se envió
        if (!empty($_FILES['archivo']['tmp_name'])) {
            $subirDrive = new SubirArchivoGoogleDrive();
            $nombreArchivo = $_FILES['archivo']['name'];
            $tmpPath = $_FILES['archivo']['tmp_name'];
            $tipoGestionNombre = '';
            foreach ($tiposDocumento as $tipo) {
                if ($tipo->Id == $_POST['tipo_documento']) {
                    $tipoGestionNombre = $tipo->Nombre;
                    break;
                }
            }
            try {
                $fechaDocumento = !empty($_POST['fecha_documento']) ? $_POST['fecha_documento'] : current_time('mysql');
                $rutaArchivo = $subirDrive->subir($tmpPath, $nombreArchivo, $nombreImportador, $tipoGestionNombre, $fechaDocumento);
            } catch (Exception $e) {
                $mensaje = '<div class="notice error">Error al subir archivo a Google Drive: ' . esc_html($e->getMessage()) . '</div>';
                error_log('Error Google Drive: ' . $e->getMessage());
            }
        }
        $dataDocumento = [
            'IdGestion'      => (int)$_POST['gestion_id'],
            'IdTipoGestion'  => (int)$_POST['tipo_documento'],
            'NombreArchivo'  => sanitize_text_field($_POST['nombre']),
            'RutaArchivo'    => $rutaArchivo,
            'UsuarioCreador' => get_current_user_id(),
            'FechaSubida'    => current_time('mysql'),
        ];
        $dataDetalle = [
            'IdTipoDocContabilidad' => !empty($_POST['tipo_doc_conta']) ? (int)$_POST['tipo_doc_conta'] : null,
            'IdTipoDocCliente'      => !empty($_POST['tipo_doc_cliente']) ? (int)$_POST['tipo_doc_cliente'] : null,
            'NombreClienteProveedor'=> sanitize_text_field($_POST['nombre_cliente'] ?? ''),
            'FechaDocumento'        => !empty($_POST['fecha_documento']) ? sanitize_text_field($_POST['fecha_documento']) : null,
            'Descripcion'           => sanitize_textarea_field($_POST['descripcion'] ?? ''),
        ];
        $casoUso = new CrearDocumentoGestion($wpdb);
        $idDoc = $casoUso->ejecutar($dataDocumento, $dataDetalle);
        if ($idDoc) {
            // Redirigir para evitar recarga infinita y limpiar POST
            header('Location: ?view=gestion_detalle&id=' . $idGestion);
            exit;
        } else {
            $mensaje = '<div class="notice error">Error al guardar el documento.<br><small>' . esc_html($wpdb->last_error) . '</small></div>';
        }
    }
}

include __DIR__ . '/../gestionDocumental/templates/gestion-detalle.php';
