<?php
define('WP_USE_THEMES', false);
require_once('../../wp-load.php');

global $wpdb;
$tabla = $wpdb->prefix . 'users';
$bitacoras = $wpdb->get_results("SELECT * FROM wp_users ");
?>

<!DOCTYPE html>

<div class="toolbar" style="margin-bottom: 20px; display: flex; gap: 10px;">
    <!-- <h1>Últimas Bitácoras</h1> -->
     <!-- Filtro global -->
    <form method="get" class="filter-form">
      <input type="hidden" name="view" value="bitacora_detalle">
      <div class="filter-grid">
        <div class="filter-field full-width input-icon-wrapper">
          <label for="q">Buscar:</label>
          <div class="input-icon-group">
            <input
              type="text"
              id="q"
              name="q"
              value=""
              placeholder="Filtrar por Tipo, Descripción, Usuario o Correo"
            >
            <button type="submit" class="icon-btn" title="Buscar">🔍</button>
            <button type="button" onclick="window.location='?view=bitacora_detalle'" class="icon-btn" title="Limpiar">✕</button>
          </div>
        </div>
      </div>
    </form>

    <a href="?view=nueva_bitacora" class="btn">➕ Nuevo Registro</a>
    <!-- <button type="button" class="btn" onclick="editarSeleccionado()">✏️ Editar</button>
    <button type="button" class="btn" onclick="exportarCSV()">📁 Exportar CSV</button>-->
</div>

<?php if (empty($bitacoras)): ?>
    <p>No hay bitácoras registradas.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>DO</th>
                <th>Nombre de Usuario</th>
                <th>Empresa</th>
                <!-- <th>Correo</th> -->
                <th>Numero BL</th>
                <th>Contenedor</th>
                <th>Estado</th>
                <th>Fecha de Creación</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bitacoras as $b): ?>
                <tr>
                    <td>I25-001</td>
                    <td><?= esc_html($b->user_login) ?></td>
                    <td><?= esc_html($b->user_login) ?></td>
                    <!-- <td><?= esc_html($b->user_login) ?></td> -->
                    <td><?= esc_html($b->user_nicename) ?></td>
                    <td><?= esc_html($b->user_email) ?></td>
                    <td>
                        <span class="status-label status-creado">
                            Creado
                        </span>
                    </td>
                    <td>18/06/2025</td>
                    <td>
                        <a class="btn" href="?view=bitacora_detalle">Ver detalle</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <!-- 4. Sección de leyenda debajo de la tabla -->
    <h2 class="legend-title">Estados</h2>
        <div class="legend-container">
            <span class="legend-box" style="background-color: #4CAF50;"></span>
            <span class="legend-label">Creado</span>
            
            <span class="legend-box" style="background-color: #FFEB3B;"></span>
            <span class="legend-label">Emitido</span>
            
            <span class="legend-box" style="background-color: #FF9800;"></span>
            <span class="legend-label">En puerto</span>
            
            <span class="legend-box" style="background-color: #F44336;"></span>
            <span class="legend-label">En transporte</span>
            
            <span class="legend-box" style="background-color: #9E9E9E;"></span>
            <span class="legend-label">Proceso completado</span>
        </div>
<?php endif; ?>