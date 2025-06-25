<?php
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
<div class="toolbar" style="margin-bottom: 20px; display: flex; gap: 10px;">
    <h1>Clientes</h1>
    <a href="?view=nueva_bitacora" class="btn btn-icon">
        <svg class="w-[18px] h-[18px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5"/>
        </svg>
        Nuevo Registro
    </a>
    <!-- <button type="button" class="btn" onclick="editarSeleccionado()">✏️ Editar</button>
    <button type="button" class="btn" onclick="exportarCSV()">📁 Exportar CSV</button>-->
</div>

<?php if (empty($bitacoras)): ?>
    <p>No hay bitácoras registradas.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Documento</th>
                <th>Razón Social</th>
                <th>Documento</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Ciudad</th>
                <th>Activo</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bitacoras as $b): ?>
                <tr>
                    <td><?= esc_html($b->user_login) ?></td>
                    <td><?= esc_html($b->user_email) ?></td>
                    <td><?= esc_html($b->rol_nombre ?: 'No Asignado') ?></td>
                    <td><?= esc_html($b->user_login) ?></td>
                    <td><?= esc_html($b->user_email) ?></td>
                    <td><?= esc_html($b->user_login) ?></td>
                    <td><?= esc_html($b->user_email) ?></td>
                    <td><?= esc_html($b->user_login) ?></td>
                    <td>
                        
                        <a style="color: #2980b9; text-decoration: none" href="<?= esc_attr($b->ID); ?>">
                            <svg class="w-[18px] h-[18px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                              <path fill-rule="evenodd" d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd"/>
                            </svg>
                        </a>
                        <a style="color: #d7ab00; text-decoration: none" href="<?= esc_attr($b->ID); ?>">
                            <svg class="w-[18px] h-[18px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                              <path fill-rule="evenodd" d="M14 4.182A4.136 4.136 0 0 1 16.9 3c1.087 0 2.13.425 2.899 1.182A4.01 4.01 0 0 1 21 7.037c0 1.068-.43 2.092-1.194 2.849L18.5 11.214l-5.8-5.71 1.287-1.31.012-.012Zm-2.717 2.763L6.186 12.13l2.175 2.141 5.063-5.218-2.141-2.108Zm-6.25 6.886-1.98 5.849a.992.992 0 0 0 .245 1.026 1.03 1.03 0 0 0 1.043.242L10.282 19l-5.25-5.168Zm6.954 4.01 5.096-5.186-2.218-2.183-5.063 5.218 2.185 2.15Z" clip-rule="evenodd"/>
                            </svg>
                        </a>
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