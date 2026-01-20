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

/**
 * Devuelve true solo si el usuario actual tiene rol ADMIN en la bitácora.
 */
function bc_es_admin_bitacora(): bool
{
    global $wpdb;
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $user_id = get_current_user_id();
    if (!$user_id) {
        $cached = false;
        return false;
    }
    $rol = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT r.Codigo 
             FROM bc_user_role ur 
             INNER JOIN bc_roles r ON r.Id = ur.IdRol 
             WHERE ur.IdUser = %d 
             LIMIT 1",
            $user_id
        )
    );
    $cached = ($rol === 'ADMIN');
    return $cached;
}

/**
 * Verifica si la tabla de historial tiene la columna Activo para borrado lógico.
 */
function bc_historial_tiene_columna_activo(): bool
{
    global $wpdb;
    static $hasColumn = null;
    if ($hasColumn !== null) {
        return $hasColumn;
    }
    $hasColumn = (bool) $wpdb->get_var("SHOW COLUMNS FROM bc_proceso_estado_historial LIKE 'Activo'");
    return $hasColumn;
}

/**
 * Lee el cuerpo JSON de la petición y devuelve un array.
 */
function bc_leer_payload(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

$es_admin_bitacora = bc_es_admin_bitacora();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'historial_estados':
        $id_proceso = intval($_GET['id_proceso'] ?? 0);
        if (!$id_proceso) {
            echo json_encode([]);
            break;
        }
        $select_activo = bc_historial_tiene_columna_activo() ? ', h.Activo' : '';
        $filtro_activo = bc_historial_tiene_columna_activo() ? ' AND COALESCE(h.Activo,1) = 1' : '';
        // Traer historial con nombres de usuario y descripciones de estado
        $historial = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT h.Id, h.IdProceso, h.EstadoAnteriorId, h.EstadoNuevoId, h.Observacion, h.IdUsuarioCambio, h.FechaCambio{$select_activo},
                        ea.Descripcion AS estado_anterior,
                        en.Descripcion AS estado_nuevo,
                        u.display_name AS usuario
                 FROM bc_proceso_estado_historial h
                 LEFT JOIN bc_estado_proceso ea ON ea.Id = h.EstadoAnteriorId
                 LEFT JOIN bc_estado_proceso en ON en.Id = h.EstadoNuevoId
                 LEFT JOIN wp_users u ON u.ID = h.IdUsuarioCambio
                 WHERE h.IdProceso = %d{$filtro_activo}
                 ORDER BY h.FechaCambio DESC",
                $id_proceso
            )
        );
        $result = array_map(function($row) {
            return [
                'id'               => $row->Id,
                'id_proceso'       => $row->IdProceso,
                'estado_anterior'  => $row->estado_anterior,
                'estado_nuevo'     => $row->estado_nuevo,
                'estado_anterior_id' => $row->EstadoAnteriorId,
                'estado_nuevo_id'  => $row->EstadoNuevoId,
                'usuario'          => $row->usuario,
                'fecha'            => $row->FechaCambio,
                'observacion'      => $row->Observacion,
                'activo'           => property_exists($row, 'Activo') ? (int)$row->Activo : 1
            ];
        }, $historial);
        echo json_encode($result);
        break;

    case 'actualizar_historial_estado':
        if (!$es_admin_bitacora) {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            break;
        }
        $payload = bc_leer_payload();
        $id_historial   = intval($payload['id'] ?? 0);
        $estado_nuevo   = intval($payload['estado_nuevo_id'] ?? 0);
        $observacion    = sanitize_text_field($payload['observacion'] ?? '');
        if (!$id_historial || !$estado_nuevo) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            break;
        }
        // Solo se permite editar el último registro activo del proceso
        $tiene_activo = bc_historial_tiene_columna_activo();
        $row = $wpdb->get_row(
            $wpdb->prepare(
                $tiene_activo
                    ? "SELECT IdProceso, FechaCambio, COALESCE(Activo,1) AS ActivoFlag FROM bc_proceso_estado_historial WHERE Id = %d"
                    : "SELECT IdProceso, FechaCambio FROM bc_proceso_estado_historial WHERE Id = %d",
                $id_historial
            )
        );
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Registro no encontrado']);
            break;
        }
        $filtro_activo = $tiene_activo ? "AND COALESCE(Activo,1) = 1" : "";
        $ultimo_id = $wpdb->get_var($wpdb->prepare(
            "SELECT Id FROM bc_proceso_estado_historial WHERE IdProceso = %d {$filtro_activo} ORDER BY FechaCambio DESC, Id DESC LIMIT 1",
            $row->IdProceso
        ));
        if (intval($ultimo_id) !== $id_historial) {
            echo json_encode(['success' => false, 'message' => 'Solo se puede editar el último estado.']);
            break;
        }
        $estado_actual_proceso = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT IdEstadoProceso FROM bc_proceso WHERE Id = %d",
                $row->IdProceso
            )
        );

        $updated = $wpdb->update(
            'bc_proceso_estado_historial',
            [
                'EstadoNuevoId'   => $estado_nuevo,
                'Observacion'     => $observacion,
                'IdUsuarioCambio' => get_current_user_id(),
                'FechaCambio'     => current_time('mysql')
            ],
            ['Id' => $id_historial]
        );
        $proceso_actualizado = false;
        if ($updated !== false) {
            // Actualizar el estado actual del proceso
            if ((int)$estado_actual_proceso !== $estado_nuevo) {
                $wpdb->update(
                    'bc_proceso',
                    ['IdEstadoProceso' => $estado_nuevo],
                    ['Id' => $row->IdProceso]
                );
                $proceso_actualizado = true;
            }
            $log_data = [
                'Objeto'        => wp_json_encode([
                    'IdHistorial'      => $id_historial,
                    'IdProceso'        => $row->IdProceso,
                    'EstadoNuevoId'    => $estado_nuevo,
                    'Observacion'      => $observacion,
                    'IdUsuarioCambio'  => get_current_user_id(),
                    'FechaCambio'      => current_time('mysql')
                ]),
                'Tabla'         => 'bc_proceso_estado_historial',
                'TipoDeCambio'  => 'Actualizar',
                'IdUser'        => get_current_user_id(),
                'FechaCreacion' => current_time('mysql'),
            ];
            $wpdb->insert('bc_logs', $log_data);
        }
        echo json_encode([
            'success' => $updated !== false,
            'updated' => (int) $updated,
            'proceso_actualizado' => $proceso_actualizado
        ]);
        break;

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
        $sql = "
            SELECT 
                TE.Descripcion AS TEDescripcion,
                TE.Codigo AS TECodigo,
                BT.FechaCreacion AS BTFechaCreacion,
                EB.*,
                BT.*,
                U.*,
                cat.Id       AS Estado,
                cat.Descripcion AS EstadoCatalogoDescripcion
            FROM bc_entrada_bitacora EB
            INNER JOIN bc_tipo_entrada TE ON TE.Id = EB.IdTipoEntrada
            INNER JOIN bc_entrada_bitacora_giro BT ON BT.IdEntradaBitacora = EB.Id
            INNER JOIN wp_users U ON U.ID = EB.IdUser
            LEFT JOIN bc_catalogo cat ON cat.Id = BT.IdEstado  -- ajusta el campo si usa otro nombre
            WHERE EB.IdProceso = %d
        ";
        $entradas = $wpdb->get_results( $wpdb->prepare($sql, $id_proceso) );


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
                'visibleCliente' => $doc->VisibleCliente,
                'fecha' => $doc->FechaCreacion,
                'idDrive' => $doc->Archivo,
                // Enlace de visualización/descarga de Google Drive
                'url'    => $downloadUrl
            ];
        }
        echo json_encode($result);
        break;

    case 'listar_documentos_cliente':
            $id_proceso = intval($_GET['id_proceso'] ?? 0);
            if (!$id_proceso) {
                echo json_encode([]);
                break;
            }
            $docs = $wpdb->get_results($wpdb->prepare("
                SELECT d.Id,
                    d.Nombre,
                    d.FechaCreacion,
                    d.Archivo
                FROM $tabla_documentos d
                INNER JOIN bc_entrada_bitacora eb ON eb.Id = d.IdEntradaBitacora
                WHERE eb.IdProceso = %d
                AND d.VisibleCliente = 1
                AND d.Activo = 1
                ORDER BY d.FechaCreacion DESC
            ", $id_proceso));
            $result = array_map(function($doc) {
                $downloadUrl = "descargar.php?id={$doc->Archivo}";
                return [
                    'id'     => $doc->Id,
                    'nombre' => $doc->Nombre,
                    'fecha'  => $doc->FechaCreacion,
                    'url'    => $downloadUrl
                ];
            }, $docs);
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
        $visible_cliente = !empty($_POST['visible_cliente']) ? 1 : 0;

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
                'VisibleCliente'    => $visible_cliente,
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
        
    case 'get_estados_giros':
    $sql = "
        SELECT Id, Descripcion
        FROM bc_catalogo
        WHERE Tipo = %s AND Activo = 1
        ORDER BY Descripcion
    ";
    $estados = $wpdb->get_results($wpdb->prepare($sql, 'EstadoGiros'));
    echo json_encode($estados);
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
