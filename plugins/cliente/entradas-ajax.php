<?php
define('WP_USE_THEMES', false);
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');

global $wpdb;

header('Content-Type: application/json');

if (!is_user_logged_in()) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_entradas_transporte':
        $id_proceso = intval($_GET['id_proceso']);
        $entradas = $wpdb->get_results("SELECT TE.Descripcion as TEDescripcion, EB.*,BT.*,U.* FROM `bc_entrada_bitacora` EB 
                                         INNER JOIN `bc_tipo_entrada` TE ON TE.Id = EB.IdTipoEntrada
                                         INNER JOIN `bc_entrada_bitacora_transporte` BT ON BT.IdEntradaBitacora = EB.Id
                                         INNER JOIN `wp_users` U On U.ID = EB.IdUser
                                         WHERE EB.IdProceso = $id_proceso");


        echo json_encode($entradas);
        break;

    case 'get_entradas_giros':
        $id_proceso = intval($_GET['id_proceso']);
        $entradas = $wpdb->get_results("SELECT TE.Descripcion as TEDescripcion, EB.*,BT.*,U.* FROM `bc_entrada_bitacora` EB 
                                         INNER JOIN `bc_tipo_entrada` TE ON TE.Id = EB.IdTipoEntrada
                                         INNER JOIN `bc_entrada_bitacora_giro` BT ON BT.IdEntradaBitacora = EB.Id
                                         INNER JOIN `wp_users` U On U.ID = EB.IdUser
                                         WHERE EB.IdProceso = $id_proceso");


        echo json_encode($entradas);
        break;
    case 'get_entradas_contabilidad':
        $id_proceso = intval($_GET['id_proceso']);
        $entradas = $wpdb->get_results("SELECT TE.Descripcion as TEDescripcion, EB.*,BT.*,U.* FROM `bc_entrada_bitacora` EB 
                                         INNER JOIN `bc_tipo_entrada` TE ON TE.Id = EB.IdTipoEntrada
                                         INNER JOIN `bc_entrada_bitacora_contabilidad` BT ON BT.IdEntradaBitacora = EB.Id
                                         INNER JOIN `wp_users` U On U.ID = EB.IdUser
                                         WHERE EB.IdProceso = $id_proceso");


        echo json_encode($entradas);
        break;

    default:
        echo json_encode(['error' => 'Acción no reconocida']);
}
exit;
