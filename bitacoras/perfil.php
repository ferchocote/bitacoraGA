<?php
define('WP_USE_THEMES', false);
require_once('../../wp-load.php');

$user = wp_get_current_user();
?>

<!DOCTYPE html>
<div class="toolbar" style="margin-bottom: 20px; display: flex; gap: 10px;">
    <h1>Mis Datos</h1>
    
    <!-- <button type="button" class="btn" onclick="editarSeleccionado()">✏️ Editar</button>
    <button type="button" class="btn" onclick="exportarCSV()">📁 Exportar CSV</button>-->
</div>
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px">
    <div><b>Nombre(s):</b> <?= esc_html($user->display_name) ?></div>
    <div><b>Correo:</b> <?= esc_html($user->user_email) ?></div>
    <div><b>Estado:</b> <?php echo ($user->user_status == 0) ? 'Activo' : 'Otro' ?></div>
    <div><b>Fecha de Creación:</b> <?= esc_html($user->user_registered) ?></div>
</div>