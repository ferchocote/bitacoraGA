<?php
// Valida rol de usuario 
if ($usuario->rol_codigo != "ADMIN" && $usuario->rol_codigo != "RRHH") {
    echo "No tienes permiso para acceder a esta vista.";
    exit;
}

define('WP_USE_THEMES', false);
require_once('../../wp-load.php');

global $wpdb;
$tabla = $wpdb->prefix . 'users';

// Parámetros actuales de la URL
$params = $_GET;

// Página actual
$pagina_actual = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;
$registros_por_pagina = 10;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Total de registros
$total_registros = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla}");
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Consulta paginada
$bitacoras = $wpdb->get_results(
    $wpdb->prepare("
        SELECT u.ID, u.user_login, u.user_email, r.Nombre AS rol_nombre
        FROM {$tabla} u
        LEFT JOIN bc_user_role ur ON ur.IdUser = u.ID
        LEFT JOIN bc_roles r ON r.Id = ur.IdRol
        LIMIT %d OFFSET %d
    ", $registros_por_pagina, $offset)
);

$roles = $wpdb->get_results("SELECT * FROM bc_roles");
?>

<!DOCTYPE html>
<h1>Usuarios</h1>

<!--<div class="toolbar" style="margin-bottom: 20px; display: flex; gap: 10px;">
    <a href="?view=nueva_bitacora" class="btn">➕ Nuevo Registro</a>
    <button type="button" class="btn" onclick="editarSeleccionado()">✏️ Editar</button>
    <button type="button" class="btn" onclick="exportarCSV()">📁 Exportar CSV</button>
</div> -->

<?php if (empty($bitacoras)): ?>
    <p>No hay bitácoras registradas.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Nombre de Usuario</th>
                <th>Correo</th>
                <th>Rol</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bitacoras as $b): ?>
                <tr>
                    <td><?= esc_html($b->user_login) ?></td>
                    <td><?= esc_html($b->user_email) ?></td>
                    <td><?= esc_html($b->rol_nombre ?: 'No Asignado') ?></td>
                    <td>
                        <label class="btn modificar-rol" for="popup-toggle" data-user="<?= esc_attr($b->ID); ?>">Modificar Rol</label>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Navegación de páginas -->
    <div style="margin-top: 20px;">
        <?php
        // Botón Anterior
        if ($pagina_actual > 1) {
            $params['pg'] = $pagina_actual - 1;
            echo '<a href="?' . http_build_query($params) . '" class="btn">⬅️ Anterior</a>';
        }

        echo " Página $pagina_actual de $total_paginas ";

        // Botón Siguiente
        if ($pagina_actual < $total_paginas) {
            $params['pg'] = $pagina_actual + 1;
            echo '<a href="?' . http_build_query($params) . '" class="btn">Siguiente ➡️</a>';
        }
        ?>
    </div>
<?php endif; ?>

<!-- POPUP DE MODIFICAR ROL -->
<input type="checkbox" id="popup-toggle">
<div class="overlay">
<div class="popup">
  <h3>Selecciona un Rol</h3>
      <div class="custom-select">
        <select id="rol" name="rol">
            <option value="">Seleccione un rol</option>
            <?php foreach ($roles as $rol): ?>
                <option value="<?= esc_attr($rol->Codigo); ?>">
                    <?= esc_html($rol->Nombre) ?>
                </option>
            <?php endforeach; ?>
        </select>
      </div>
    <label for="popup-toggle" class="close">Cerrar</label>
    <label for="popup-toggle" id="aceptar" class="btn">Aceptar</label>
</div>
</div>

<script>
    const ajaxUrl = "<?= admin_url('admin-ajax.php'); ?>";
    const ajaxNonce = "<?= wp_create_nonce('modificar_rol_nonce'); ?>";

    document.addEventListener('DOMContentLoaded', function () {
        let userId = null;
        
        document.querySelectorAll('.modificar-rol').forEach(function (btn) {
            btn.addEventListener('click', function () {
                userId = this.dataset.user;
            });
        });
        
        document.querySelector('#aceptar').addEventListener('click', function () {
            const select = document.querySelector('#rol');
            const rolCodigo = select.value;
            modificarRol(rolCodigo);
        })
    
        function modificarRol(rolCodigo) {
            fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'modificar_rol_usuario',
                    security: ajaxNonce,
                    userId: userId,
                    rolCodigo: rolCodigo
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Rol modificado correctamente');
                    location.reload();
                } else {
                    alert('Error: ' + data.data);
                }
            });
        }
    });
</script>