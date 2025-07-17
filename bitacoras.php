<?php
define('WP_USE_THEMES', false);
require_once('../../wp-load.php');

global $wpdb;

$q = '';
$where_sql = '';

// Nombre real de tu tabla, ajusta el prefijo si es necesario:
$tabla = 'bc_' . 'proceso';
$tabla_estados = 'bc_' . 'estado_proceso';
$tabla_cliente = 'bc_' . 'cliente';

if ( ! empty($_GET['q']) ) {
    $q    = sanitize_text_field($_GET['q']);
    $like = '%' . $q . '%';
    // Filtro global en varias columnas
    $where_sql = $wpdb->prepare(
        "WHERE p.DO          LIKE %s
            OR u.user_login LIKE %s
            OR p.NumeroBL    LIKE %s
            OR p.Contenedor  LIKE %s",
        $like, $like, $like, $like
    );
}

// Parámetros de paginación
$per_page = 10;
$page     = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset   = ($page - 1) * $per_page;

// Total de procesos con filtro
$total = $wpdb->get_var(
    "SELECT COUNT(*)
     FROM {$tabla} p
     LEFT JOIN {$wpdb->prefix}users u ON u.ID = p.IdUserCreation
     $where_sql"
);

// Consulta paginada con filtro
$sql = $wpdb->prepare(
    "
    SELECT 
      p.Id,
      p.DO,
      u.user_login      AS creador,
      c.RazonSocial,
      p.NumeroBL,
      p.Contenedor,
      ep.Codigo              AS EstadoCodigo,
      ep.Descripcion         AS EstadoDescripcion,
      ep.Color               AS EstadoColor,
      p.FechaCreacion
    FROM {$tabla} p
    LEFT JOIN {$wpdb->prefix}users u
      ON u.ID = p.IdUserCreation
    LEFT JOIN {$tabla_estados} ep
      ON ep.ID = p.IdEstadoProceso
    LEFT JOIN {$tabla_cliente} c
      ON c.ID = p.IdImportador
    $where_sql
    ORDER BY p.FechaCreacion DESC
    LIMIT %d OFFSET %d
    ",
    $per_page,
    $offset
);
$procesos = $wpdb->get_results( $sql );

// Traemos solo los activos y en el orden lógico
$estados = $wpdb->get_results(
  "SELECT Descripcion, Color 
   FROM {$tabla_estados}
   WHERE Activo = 1
   ORDER BY Id"
);

?>

<!DOCTYPE html>

<div class="toolbar" style="margin-bottom: 20px; display: flex; gap: 10px;">

    <form method="get" action="" class="filter-form">
      <input type="hidden" name="view" value="bitacoras">
      <div class="filter-grid">
        <div class="filter-field full-width input-icon-wrapper">
          <label for="q">Buscar:</label>
          <div class="input-icon-group">
            <input
              type="text"
              id="q"
              name="q"
              value="<?= esc_attr( $q ) ?>"
              placeholder="Filtrar por DO, Usuario, BL o Contenedor"
            >
            <button type="submit" class="icon-btn" title="Buscar">🔍</button>
            <!-- <button type="button" onclick="window.location='?view=bitacora_detalle'" class="icon-btn" title="Limpiar">✕</button> -->
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
                <th>Encargado</th>
                <th>Importador</th>
                <th>Numero BL</th>
                <th>Contenedor</th>
                <th>Estado</th>
                <th>Fecha de Creación</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $procesos ) ): ?>
             <tr><td colspan="7">No hay procesos registrados.</td></tr>
            <?php else: ?>
            <?php foreach ( $procesos as $p ): ?>
            <tr>
                <td><?= esc_html( $p->DO ) ?></td>
                <td><?= esc_html( $p->creador ) ?></td>
                <td><?= esc_html( $p->RazonSocial ) ?></td>
                <td><?= esc_html( $p->NumeroBL ) ?></td>
                <td><?= esc_html( $p->Contenedor ) ?></td>
                <td>
                <span class="status-label status-<?= strtolower($p->EstadoCodigo) ?>">
                    <?= esc_html( $p->EstadoDescripcion ) ?>
                </span>
                </td>
                <td><?= esc_html( date('d/m/Y', strtotime($p->FechaCreacion)) ) ?></td>
                <td>
                <a class="btn" href="?view=bitacora_detalle&id=<?= esc_attr($p->Id) ?>">Ver detalle</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

<?php
// 3) Renderizado del paginador
$total_pages = ceil( $total / $per_page );
if ( $total_pages > 1 ): ?>
  <div class="pagination">
    <?php if ( $page > 1 ): ?>
      <a href="?view=bitacoras&paged=<?= $page - 1 ?>">&laquo; Anterior</a>
    <?php endif; ?>
    <?php for ( $i = 1; $i <= $total_pages; $i++ ): ?>
      <?php if ( $i == $page ): ?>
        <span class="current"><?= $i ?></span>
      <?php else: ?>
        <a href="?view=bitacoras&paged=<?= $i ?>"><?= $i ?></a>
      <?php endif; ?>
    <?php endfor; ?>
    <?php if ( $page < $total_pages ): ?>
      <a href="?view=bitacoras&paged=<?= $page + 1 ?>">Siguiente &raquo;</a>
    <?php endif; ?>
  </div>
<?php endif; ?>

    <!-- 4. Sección de leyenda debajo de la tabla -->
    <h2 class="legend-title">Estados</h2>
    <div class="legend-container">
      <?php foreach ( $estados as $st ): ?>
        <div class="legend-item">
          <span 
            class="legend-box" 
            style="background-color: <?= esc_attr( $st->Color ) ?>;"
          ></span>
          <span class="legend-label">
            <?= esc_html( $st->Descripcion ) ?>
          </span>
        </div>
      <?php endforeach; ?>
    </div>
<?php endif; ?>