<?php
if (!function_exists('crear_gestion_documental')) {
    function crear_gestion_documental($wpdb, $idImportador, $idUsuario)
    {
        $datos = [
            'IdImportador'   => (int) $idImportador,
            'UsuarioCreacion'=> (int) $idUsuario,
            'FechaCreacion'  => current_time('mysql'),
        ];

        $formatos = ['%d', '%d', '%s'];

        if (isset($_POST['observaciones']) && $_POST['observaciones'] !== '') {
            $datos['Observaciones'] = sanitize_textarea_field($_POST['observaciones']);
            $formatos[] = '%s';
        }

        $resultado = $wpdb->insert('bc_gestion_documental', $datos, $formatos);

        if (!$resultado) {
            return false;
        }

        return (int) $wpdb->insert_id;
    }
}
