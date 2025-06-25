<?php
define('WP_USE_THEMES', false);
require_once('../../wp-load.php');

global $wpdb;
$roles = $wpdb->get_results("SELECT Codigo, Nombre, Descripcion FROM bc_roles");
?>

<!DOCTYPE html>
<h1>Roles</h1>

<?php if (empty($roles)): ?>
    <p>No hay bitácoras registradas.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Descripción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($roles as $b): ?>
                <tr>
                    <td><?= esc_html($b->Codigo) ?></td>
                    <td><?= esc_html($b->Nombre) ?></td>
                    <td><?= esc_html($b->Descripcion) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>