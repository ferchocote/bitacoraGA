<?php
require_once('../../wp-load.php');

global $wpdb;
$tabla = $wpdb->prefix . 'users';
$bitacoras = $wpdb->get_results("SELECT * FROM wp_users ");
?>


<!DOCTYPE html>

<?php if (empty($bitacoras)): ?>
    <p>No hay entradas en la bitácora.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Descripción</th>
                <th>Nombre de Usuario</th>
                <th>Correo</th>
                <th>Fecha de Creación</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bitacoras as $b): ?>
                <tr>
                    <td>Trasnporte</td>
                    <td><?= esc_html($b->user_login) ?></td>
                    <td><?= esc_html($b->user_nicename) ?></td>
                    <td><?= esc_html($b->user_email) ?></td>
                    <td>18/06/2025</td>
                    <td>
                        <a class="btn">Ver detalle</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>