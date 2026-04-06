<?php

if (!function_exists('bc_suscripcion_esta_bloqueada')) {
    function bc_suscripcion_esta_bloqueada($wpdb) {
        static $cache = [];

        $cacheKey = is_object($wpdb) ? spl_object_hash($wpdb) : 'default';
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $tabla = 'bc_suscripcion';
        $tablaExiste = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla));

        if ($tablaExiste !== $tabla) {
            $cache[$cacheKey] = false;
            return false;
        }

        $activo = $wpdb->get_var("SELECT activa FROM {$tabla} LIMIT 1");
        if ($activo === null) {
            $cache[$cacheKey] = false;
            return false;
        }

        if (is_bool($activo)) {
            $cache[$cacheKey] = ($activo === false);
            return $cache[$cacheKey];
        }

        $valorNormalizado = strtolower(trim((string) $activo));
        $cache[$cacheKey] = in_array($valorNormalizado, ['0', 'false', 'f', 'no', 'off', ''], true);

        return $cache[$cacheKey];
    }
}

if (!function_exists('bc_suscripcion_mensaje_bloqueo')) {
    function bc_suscripcion_mensaje_bloqueo() {
        return 'Esta funcionalidad ha sido deshabilitada debido al vencimiento de su suscripción.';
    }
}

if (!function_exists('bc_suscripcion_alerta_bloqueo_html')) {
    function bc_suscripcion_alerta_bloqueo_html() {
        return '<div class="error">' . esc_html(bc_suscripcion_mensaje_bloqueo()) . '</div>';
    }
}

if (!function_exists('bc_suscripcion_render_bloqueo_y_salir')) {
    function bc_suscripcion_render_bloqueo_y_salir($titulo = 'Funcionalidad deshabilitada') {
        $mensaje = bc_suscripcion_mensaje_bloqueo();

        status_header(403);
        echo '<!DOCTYPE html>';
        echo '<html lang="es"><head><meta charset="UTF-8"><title>' . esc_html($titulo) . '</title></head><body style="font-family: Arial, sans-serif; background: #f5f6fa; padding: 32px;">';
        echo '<div style="max-width: 680px; margin: 0 auto; background: #fff; border: 1px solid #f2dede; border-radius: 8px; padding: 24px; color: #a94442;">';
        echo '<h1 style="margin-top: 0; font-size: 24px;">' . esc_html($titulo) . '</h1>';
        echo '<p style="margin-bottom: 0;">' . esc_html($mensaje) . '</p>';
        echo '</div></body></html>';
        exit;
    }
}
