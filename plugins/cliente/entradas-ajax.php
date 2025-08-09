<?php
define('WP_USE_THEMES', false);
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
require_once(__DIR__ . '/../../../google-drive/vendor/autoload.php');

global $wpdb;
$tabla_documentos = 'bc_documento';

header('Content-Type: application/json');

if (!is_user_logged_in()) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_entradas_transporte':
        $id_proceso = intval($_GET['id_proceso']);
        $entradas = $wpdb->get_results("SELECT TE.Descripcion as TEDescripcion,TE.Codigo as TECodigo,BT.FechaCreacion as BTFechaCreacion, EB.*,BT.*,U.* FROM `bc_entrada_bitacora` EB 
                                         INNER JOIN `bc_tipo_entrada` TE ON TE.Id = EB.IdTipoEntrada
                                         INNER JOIN `bc_entrada_bitacora_transporte` BT ON BT.IdEntradaBitacora = EB.Id
                                         INNER JOIN `wp_users` U On U.ID = EB.IdUser
                                         WHERE EB.IdProceso = $id_proceso");


        echo json_encode($entradas);
        break;

    case 'get_entradas_giros':
        $id_proceso = intval($_GET['id_proceso']);
        $entradas = $wpdb->get_results("SELECT TE.Descripcion as TEDescripcion,TE.Codigo as TECodigo,BT.FechaCreacion as BTFechaCreacion, EB.*,BT.*,U.* FROM `bc_entrada_bitacora` EB 
                                         INNER JOIN `bc_tipo_entrada` TE ON TE.Id = EB.IdTipoEntrada
                                         INNER JOIN `bc_entrada_bitacora_giro` BT ON BT.IdEntradaBitacora = EB.Id
                                         INNER JOIN `wp_users` U On U.ID = EB.IdUser
                                         WHERE EB.IdProceso = $id_proceso");


        echo json_encode($entradas);
        break;
    case 'get_entradas_contabilidad':
        $id_proceso = intval($_GET['id_proceso']);
        $entradas = $wpdb->get_results("SELECT TE.Descripcion as TEDescripcion,TE.Codigo as TECodigo,BT.FechaCreacion as BTFechaCreacion, EB.*,BT.*,U.* FROM `bc_entrada_bitacora` EB 
                                         INNER JOIN `bc_tipo_entrada` TE ON TE.Id = EB.IdTipoEntrada
                                         INNER JOIN `bc_entrada_bitacora_contabilidad` BT ON BT.IdEntradaBitacora = EB.Id
                                         INNER JOIN `wp_users` U On U.ID = EB.IdUser
                                         WHERE EB.IdProceso = $id_proceso");


        echo json_encode($entradas);
        break;

    case 'listar_documentos':
        $id_entrada = intval($_GET['id_entrada']);

        $docs = $wpdb->get_results($wpdb->prepare("SELECT * FROM $tabla_documentos WHERE IdEntradaBitacora = %d AND Activo = 1", $id_entrada));
        $result = [];
        foreach ($docs as $doc) {
            $downloadUrl = "descargar.php?id={$doc->Archivo}";
            $result[] = [
                'id'     => $doc->Id,
                'nombre' => $doc->Nombre,
                'fecha' => $doc->FechaCreacion,
                'idDrive' => $doc->Archivo,
                // Enlace de visualización/descarga de Google Drive
                'url'    => $downloadUrl
            ];
        }
        echo json_encode($result);
        break;

    case 'subir_documento':
        $id_entrada = intval($_POST['id_entrada']);
        // Obtener datos de la entrada para DO y tipo
        $entrada = $wpdb->get_row($wpdb->prepare(
            "SELECT P.DO, TE.Descripcion as TipoDescripcion, C.RazonSocial as Cliente, E.RazonSocial as Empresa
             FROM bc_entrada_bitacora EB
             INNER JOIN bc_proceso P ON P.Id = EB.IdProceso             
             INNER JOIN bc_tipo_entrada TE ON TE.Id = EB.IdTipoEntrada             
             LEFT JOIN bc_cliente C ON C.Id = P.IdCliente
             LEFT JOIN bc_cliente E ON E.Id = P.IdImportador
             WHERE EB.Id = %d",
            $id_entrada
        ));
        if (!$entrada) {
            echo json_encode(['success' => false, 'msg' => 'Entrada no encontrada']);
            exit;
        }
        
       
        $do = $entrada->DO;
        $tipoDescripcion = $entrada->TipoDescripcion;

        // Para $empresa
        if (isset($entrada->Empresa) && !empty($entrada->Empresa)) {
            // La propiedad Empresa existe en $entrada y tiene un valor no vacío
            $empresa = $entrada->Empresa;
           
        } else {
            // La propiedad Empresa no existe, es null, o es una cadena vacía, 0, etc.
            echo json_encode(['success' => false, 'msg' => 'Empresa no relacionada para cargar documentos']);
            exit;
        }

        // Para $cliente
        if (isset($entrada->Cliente) && !empty($entrada->Cliente)) {
            $cliente = $entrada->Cliente;
           
        } else {
            echo json_encode(['success' => false, 'msg' => 'Cliente no relacionada para cargar documentos']);
            exit;
        }


        if (!empty($_FILES['archivo']['name'])) {
            $nombre = $_FILES['archivo']['name'];
            $tmp = $_FILES['archivo']['tmp_name'];

            // --- 1. Validar si el archivo ya existe en la base de datos para esta entrada ---
            $existing_db_file_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_documentos WHERE IdEntradaBitacora = %d AND Nombre = %s AND Activo = 1",
                $id_entrada,
                $nombre
            ));

            if ($existing_db_file_count > 0) {
                echo json_encode(['success' => false, 'msg' => 'El archivo con este nombre ya existe para esta entrada en la base de datos.']);
                exit;
            }

            // --- Google Drive ---
            $client = new Google_Client();
            $client->setApplicationName('Acceso Drive desde WordPress');
            $client->setAuthConfig(__DIR__ . '/../../environment/service_account_cred.json');
            $client->setSubject('subgerencia@galogistic.com');
            $client->setScopes([Google_Service_Drive::DRIVE]);
            $driveService = new Google_Service_Drive($client);
            $sharedDriveId = '0APg0nAAp2LMpUk9PVA'; // Ajusta si usas Shared Drives

            // Buscar o crear carpeta DO

            $parentFolderEmpresa = buscarOCrearCarpeta($driveService, $empresa, $sharedDriveId, $sharedDriveId);
            // Buscar o crear subcarpeta tipo
            $childFolderCliente = buscarOCrearCarpeta($driveService, $cliente, $sharedDriveId, $parentFolderEmpresa);
            $childFolderDO = buscarOCrearCarpeta($driveService, $do, $sharedDriveId, $childFolderCliente);
            $childFolderTipoEntrda = buscarOCrearCarpeta($driveService, $tipoDescripcion, $sharedDriveId, $childFolderDO);

            // Subir archivo
            $fileMetadata = new Google_Service_Drive_DriveFile([
                'name' => $nombre,
                'parents' => [$childFolderTipoEntrda]
            ]);
            $content = file_get_contents($tmp);
            $file = $driveService->files->create($fileMetadata, [
                'data' => $content,
                'uploadType' => 'multipart',
                'fields' => 'id',
                'supportsAllDrives' => true
            ]);
            $idDrive = $file->id;
            $wpdb->show_errors();


            // Guardar en la base de datos
            $wpdb->insert($tabla_documentos, [
                'IdEntradaBitacora' => $id_entrada,
                'Archivo'           => $idDrive, // ID de Google Drive
                'Nombre'            => $nombre,  // Nombre original del archivo
                'Activo'            => 1,
                'FechaCreacion'     => current_time('mysql')
            ]);

            echo json_encode(['success' => true, 'id' => $wpdb->insert_id, 'nombre' => $nombre, 'url' => 'https://drive.google.com/file/d/' . $idDrive . '/view?usp=sharing']);
        } else {
            echo json_encode(['success' => false]);
        }
        break;

    case 'eliminar_documento':
        $id = intval($_GET['id'] ?? $_POST['id']);
        $doc = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_documentos WHERE id = %d", $id));
        if ($doc) {
            $file = WP_CONTENT_DIR . '/uploads/bitacora_docs/' . $doc->archivo;
            if (file_exists($file)) unlink($file);
            $wpdb->delete($tabla_documentos, ['id' => $id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;

    default:
        echo json_encode(['error' => 'Acción no reconocida']);
}
exit;

// --- Función para buscar o crear carpeta ---
function buscarOCrearCarpeta($driveService, $nombre, $sharedDriveId, $parentId)
{
    $query = sprintf("name='%s' and mimeType='application/vnd.google-apps.folder' and '%s' in parents and trashed=false", addslashes($nombre), $parentId);
    $params = [
        'q' => $query,
        'fields' => 'files(id, name)',
        'supportsAllDrives' => true,
        'includeItemsFromAllDrives' => true,
        'corpora' => 'drive',
        'driveId' => $sharedDriveId
    ];
    $results = $driveService->files->listFiles($params);
    if (count($results->getFiles()) > 0) {
        return $results->getFiles()[0]->getId();
    }
    $fileMetadata = new Google_Service_Drive_DriveFile([
        'name' => $nombre,
        'mimeType' => 'application/vnd.google-apps.folder',
        'parents' => [$parentId]
    ]);
    $folder = $driveService->files->create($fileMetadata, [
        'fields' => 'id',
        'supportsAllDrives' => true
    ]);
    return $folder->id;
}
