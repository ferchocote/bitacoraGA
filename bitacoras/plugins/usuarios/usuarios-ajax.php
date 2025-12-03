<?php
define('WP_USE_THEMES', false);
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');


global $wpdb;

header('Content-Type: application/json');

if (!is_user_logged_in()) {
    echo json_encode(['success' => false, 'data' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'modificar_rol_usuario':
        // Verificar nonce
        if (!check_ajax_referer('modificar_rol_nonce', 'security', false)) {
            echo json_encode(['success' => false, 'data' => 'Error de seguridad - nonce inválido']);
            exit;
        }

        // Sanitización segura de IDs
        $user = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
        $rol  = isset($_POST['rolCodigo']) ? sanitize_text_field($_POST['rolCodigo']) : '';
        $aliado = isset($_POST['aliadoId']) ? intval($_POST['aliadoId']) : 0;

        // Validar que todos los campos estén presentes
        if (empty($user) || empty($rol) || empty($aliado)) {
            echo json_encode(['success' => false, 'data' => "Todos los campos son obligatorios. User: $user, Rol: $rol, Aliado: $aliado"]);
            exit;
        }

        // Obtener RolID por Código
        $rol_id = $wpdb->get_var(
            $wpdb->prepare("SELECT Id FROM bc_roles WHERE Codigo = %s", $rol)
        );
        
        if (!$rol_id) {
            echo json_encode(['success' => false, 'data' => "Rol no válido: $rol"]);
            exit;
        }
        
        // Verificar si existe en bc_user_role
        $existe = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM bc_user_role WHERE IdUser = %d", $user)
        );

        // Actualizar bc_user_role
        if ($existe > 0) {
            // Update si ya existe
            $resultado_rol = $wpdb->update(
                'bc_user_role',
                ['IdRol' => $rol_id],
                ['IdUser' => $user],
                ['%d'],
                ['%d']
            );
        } else {
            // Insert si no existe
            $resultado_rol = $wpdb->insert(
                'bc_user_role',
                ['IdUser' => $user, 'IdRol' => $rol_id],
                ['%d', '%d']
            );
        }
        
        // Actualizar IdAliado en wp_users
        $resultado_aliado = $wpdb->update(
            'wp_users',
            ['IdAliado' => $aliado],
            ['ID' => $user],
            ['%d'],
            ['%d']
        );

        if ($resultado_rol !== false && $resultado_aliado !== false) {
            echo json_encode(['success' => true, 'data' => 'Rol y aliado actualizados correctamente']);
        } else {
            $error_msg = "Error al actualizar. ";
            if ($resultado_rol === false) $error_msg .= "Error en rol. ";
            if ($resultado_aliado === false) $error_msg .= "Error en aliado. ";
            $error_msg .= "DB Error: " . $wpdb->last_error;
            echo json_encode(['success' => false, 'data' => $error_msg]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'data' => 'Acción no válida']);
        break;
}
