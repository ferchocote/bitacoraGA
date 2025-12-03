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
        SELECT u.ID, u.user_login, u.user_email, r.Nombre AS rol_nombre, ge.Nombre AS aliado_nombre
        FROM {$tabla} u
        LEFT JOIN bc_user_role ur ON ur.IdUser = u.ID
        LEFT JOIN bc_roles r ON r.Id = ur.IdRol
        LEFT JOIN bc_grupo_empresa ge ON ge.Id = u.IdAliado
        LIMIT %d OFFSET %d
    ", $registros_por_pagina, $offset)
);

$roles = $wpdb->get_results("SELECT * FROM bc_roles");
$aliados = $wpdb->get_results("SELECT * FROM bc_grupo_empresa ORDER BY Nombre");
?>
<script src="/wp-content/bitacoras/assets/js/common-loader.js"></script>
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
                <th>Aliado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bitacoras as $b): ?>
                <tr>
                    <td><?= esc_html($b->user_login) ?></td>
                    <td><?= esc_html($b->user_email) ?></td>
                    <td><?= esc_html($b->rol_nombre ?: 'No Asignado') ?></td>
                    <td><?= esc_html($b->aliado_nombre ?: 'No Asignado') ?></td>
                    <td>
                        <label class="btn modificar-rol" for="popup-toggle" data-user="<?= esc_attr($b->ID); ?>">Gestionar Usuario</label>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Navegación de páginas -->
    <div class="form-buttons" style="margin-top: 20px;">
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
    <div id="loader-overlay">
        <div class="spinner"></div>
    </div>
<?php endif; ?>

<!-- POPUP DE MODIFICAR ROL -->
<input type="checkbox" id="popup-toggle">
<div class="overlay">
    <div class="popup" style="max-width: 380px;">
        <h3>Selecciona Rol y Aliado</h3>
        <div class="custom-select" style="margin-bottom: 15px;">
            <label for="rol" style="display: block; margin-bottom: 5px; font-weight: bold;">Rol *</label>
            <select id="rol" name="rol" required>
                <option value="">Seleccione un rol</option>
                <?php foreach ($roles as $rol): ?>
                    <option value="<?= esc_attr($rol->Codigo); ?>">
                        <?= esc_html($rol->Nombre) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="custom-select" style="margin-bottom: 15px;">
            <label for="aliado" style="display: block; margin-bottom: 5px; font-weight: bold;">Aliado *</label>
            <select id="aliado" name="aliado" required>
                <option value="">Seleccione un aliado</option>
                <?php foreach ($aliados as $aliado): ?>
                    <option value="<?= esc_attr($aliado->Id); ?>">
                        <?= esc_html($aliado->Nombre) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-buttons" style="margin-top: 10px;">
            <label for="popup-toggle" class="btn close">Cerrar</label>
            <button type="button" id="aceptar" class="btn">Aceptar</button>
        </div>
    </div>
</div>

<script>
    const ajaxUrl = '/wp-content/bitacoras/plugins/usuarios/usuarios-ajax.php';
    const ajaxNonce = "<?= wp_create_nonce('modificar_rol_nonce'); ?>";


    document.addEventListener('DOMContentLoaded', function() {
        let userId = null;

        document.querySelectorAll('.modificar-rol').forEach(function(btn) {
            btn.addEventListener('click', function() {
                userId = this.dataset.user;
            });
        });

        document.querySelector('#aceptar').addEventListener('click', function() {
            const rolSelect = document.querySelector('#rol');
            const aliadoSelect = document.querySelector('#aliado');
            const rolCodigo = rolSelect.value;
            const aliadoId = aliadoSelect.value;
            
            if (!rolCodigo || !aliadoId) {
                alert('Por favor seleccione tanto el rol como el aliado');
                return;
            }
            
            if (!userId) {
                alert('Error: No se ha seleccionado un usuario');
                return;
            }
            
            modificarRol(rolCodigo, aliadoId);
        })
        // Selecciona todos los enlaces dentro del sidebar (tu menú principal)
        const sidebarLinks = document.querySelectorAll('.form-buttons a');



        // Función auxiliar para añadir el evento de clic a una colección de enlaces
        function addLoaderToLinks(links) {
            links.forEach(function(link) {
                // Añade un listener de clic a cada enlace
                link.addEventListener('click', function() {
                    // Llama a la función showLoader() que está en common-loader.js
                    hideLoader();
                    showLoader();
                });
            });
        }

        // Aplica la función a los enlaces del sidebar
        addLoaderToLinks(sidebarLinks);

        function modificarRol(rolCodigo, aliadoId) {
            fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'modificar_rol_usuario',
                        security: ajaxNonce,
                        userId: userId,
                        rolCodigo: rolCodigo,
                        aliadoId: aliadoId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Rol y aliado modificados correctamente');
                        document.getElementById('popup-toggle').checked = false;
                        location.reload();
                    } else {
                        alert('Error: ' + data.data);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión: ' + error.message);
                });
        }
    });
</script>