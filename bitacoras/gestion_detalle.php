<?php
// Controlador para el detalle de gestión documental
require_once __DIR__ . '/../gestionDocumental/includes/repositories/DocumentoGestionRepository.php';
require_once __DIR__ . '/../gestionDocumental/includes/usecases/ListarTiposDocumentos.php';
require_once __DIR__ . '/../gestionDocumental/includes/usecases/CrearDocumentoGestion.php';
require_once __DIR__ . '/../gestionDocumental/includes/usecases/SubirArchivoGoogleDrive.php';

global $wpdb;

$idGestion = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$q = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
if ($idGestion <= 0) {
    echo 'Gestión no válida.';
    exit;
}

$repoDoc = new DocumentoGestionRepository($wpdb);

$per_page = 10;
$page = max(1, isset($_GET['paged']) ? (int) $_GET['paged'] : 1);
$offset = ($page - 1) * $per_page;

$total_docs = $repoDoc->contarPorGestion($idGestion, $q);
$documentos = $repoDoc->obtenerPorGestion($idGestion, $per_page, $offset, $q);

$documentosAgrupados = [];
foreach ($documentos as $doc) {
    $tipo = $doc->TipoNombre ?? __('Sin tipo', 'gestion-documental');
    $fecha = !empty($doc->FechaSubida) ? date('Y-m-d', strtotime($doc->FechaSubida)) : current_time('Y-m-d');
    $documentosAgrupados[$tipo][$fecha][] = $doc;
}

$listarTipos = new ListarTiposDocumentos($wpdb);
$tiposData = $listarTipos->obtenerTipos();
$tiposDocumento = $tiposData['tiposDocumento'];
$tiposDocContabilidad = $tiposData['tiposDocContabilidad'];
$tiposDocCliente = $tiposData['tiposDocCliente'];

$gestionId = $idGestion;
$mensaje = '';

$gestionExiste = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM bc_gestion_documental WHERE ID = %d', $idGestion));
$nombreImportador = $wpdb->get_var($wpdb->prepare(
    'SELECT c.RazonSocial
     FROM bc_gestion_documental gd
     INNER JOIN bc_cliente c ON c.ID = gd.IdImportador
     WHERE gd.ID = %d',
    $idGestion
));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gestion_id'], $_POST['tipo_documento'], $_POST['nombre'])) {
    if (!isset($_POST['documento_gestion_nonce']) || !wp_verify_nonce($_POST['documento_gestion_nonce'], 'crear_documento_gestion')) {
        $mensaje = '<div class="notice error">La solicitud no es válida. Recarga la página e inténtalo de nuevo.</div>';
    } elseif (!$gestionExiste || (int) $_POST['gestion_id'] !== $idGestion) {
        $mensaje = '<div class="notice error">No se puede guardar: la gestión seleccionada no existe.</div>';
    } else {
        $rutaArchivo = '';
        if (!empty($_FILES['archivo']['tmp_name'])) {
            $subirDrive = new SubirArchivoGoogleDrive();
            $nombreArchivo = sanitize_file_name(wp_unslash($_FILES['archivo']['name']));
            $tmpPath = $_FILES['archivo']['tmp_name'];
            $tipoGestionNombre = '';
            foreach ($tiposDocumento as $tipo) {
                if ((int) $tipo->Id === (int) $_POST['tipo_documento']) {
                    $tipoGestionNombre = $tipo->Nombre;
                    break;
                }
            }
            try {
                $fechaDocumento = !empty($_POST['fecha_documento']) ? sanitize_text_field(wp_unslash($_POST['fecha_documento'])) : current_time('mysql');
                $rutaArchivo = $subirDrive->subir($tmpPath, $nombreArchivo, $nombreImportador, $tipoGestionNombre, $fechaDocumento);
            } catch (Exception $e) {
                $mensaje = '<div class="notice error">Error al subir archivo a Google Drive: ' . esc_html($e->getMessage()) . '</div>';
                error_log('Error Google Drive: ' . $e->getMessage());
            }
        }

        if (empty($mensaje)) {
            $dataDocumento = [
                'IdGestion'      => (int) $_POST['gestion_id'],
                'IdTipoGestion'  => (int) $_POST['tipo_documento'],
                'NombreArchivo'  => sanitize_text_field(wp_unslash($_POST['nombre'])),
                'RutaArchivo'    => $rutaArchivo,
                'UsuarioCreador' => get_current_user_id(),
                'FechaSubida'    => current_time('mysql'),
            ];

            $dataDetalle = [
                'IdCliente'             => !empty($_POST['cliente_proveedor']) ? (int) $_POST['cliente_proveedor'] : null,
                'IdTipoDocContabilidad' => !empty($_POST['tipo_doc_conta']) ? (int) $_POST['tipo_doc_conta'] : null,
                'FechaDocumento'        => !empty($_POST['fecha_documento']) ? sanitize_text_field(wp_unslash($_POST['fecha_documento'])) : null,
                'Descripcion'           => sanitize_textarea_field(isset($_POST['descripcion']) ? wp_unslash($_POST['descripcion']) : ''),
            ];

            $casoUso = new CrearDocumentoGestion($wpdb);
            $idDoc = $casoUso->ejecutar($dataDocumento, $dataDetalle);
            if ($idDoc) {
                wp_safe_redirect(add_query_arg(['view' => 'gestion_detalle', 'id' => $idGestion]));
                exit;
            } else {
                $mensaje = '<div class="notice error">Error al guardar el documento.<br><small>' . esc_html($wpdb->last_error) . '</small></div>';
            }
        }
    }
}

include __DIR__ . '/../gestionDocumental/templates/gestion-detalle.php';
