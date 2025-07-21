<?php
define('WP_USE_THEMES', false);
require_once('../../wp-load.php');

global $wpdb;
$current_user_id = get_current_user_id();

$q    = '';
$searchTerm    = '';
$where_clauses = [];
$params        = [];

// Nombre real de tu tabla, ajusta el prefijo si es necesario:
$tabla = 'bc_' . 'proceso';
$tabla_estados = 'bc_' . 'estado_proceso';
$tabla_cliente = 'bc_' . 'cliente';


// 1) Recuperamos el Id interno del rol “CLIENTE”
$tabla_roles     = 'bc_roles';           // o "{$wpdb->prefix}bc_rol" si usas prefijo
$cliente_rol_id  = (int) $wpdb->get_var("
    SELECT Id 
    FROM {$tabla_roles} 
    WHERE Codigo = 'CLIENTE'
    LIMIT 1
");

$tabla_user_rol = 'bc_user_role';
$is_cliente_custom = (bool) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) 
         FROM {$tabla_user_rol} 
         WHERE IdUser = %d 
           AND IdRol  = %d",
        $current_user_id,
        $cliente_rol_id
    )
);

if ( ! empty( $_GET['q'] ) ) {
  // 1) el valor limpio para mostrar
  $searchTerm = sanitize_text_field( $_GET['q'] );
  // 2) la versión con % para la consulta
  $like = '%' . $wpdb->esc_like( $searchTerm ) . '%';

  $where_clauses[] = "(
    p.DO           LIKE %s
    OR u.user_login LIKE %s
    OR p.NumeroBL    LIKE %s
    OR p.Contenedor  LIKE %s
  )";
  // rellenamos los parámetros con la versión con %…
  array_push( $params, $like, $like, $like, $like );
}

if ( $is_cliente_custom ) {
    $where_clauses[] = 'p.IDCliente = %d';
    $params[]        = $current_user_id;
}

$where_sql = $where_clauses
  ? 'WHERE ' . implode(' AND ', $where_clauses)
  : '';

// 1) Conteo total
$count_sql = "
  SELECT COUNT(*)
  FROM bc_proceso p
  LEFT JOIN {$wpdb->prefix}users u ON u.ID = p.IdUserCreation
  {$where_sql}
";
// Si no hay placeholders, no llamamos a prepare()
if ( ! empty( $params ) ) {
  $total = intval( $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) );
} else {
  $total = intval( $wpdb->get_var( $count_sql ) );
}

// 3) Consulta paginada
$per_page = 10;
$page     = max( 1, intval( $_GET['paged'] ?? 1 ) );
$offset   = ( $page - 1 ) * $per_page;

// 2) Consulta paginada (siempre tiene LIMIT %d OFFSET %d, así que sí prepararemos)
$params[] = $per_page;
$params[] = $offset;

$select_sql = "
  SELECT 
    p.Id, p.DO,
    u.user_login  AS creador,
    c.RazonSocial,
    p.NumeroBL, p.Contenedor,
    ep.Codigo              AS EstadoCodigo,
    ep.Descripcion         AS EstadoDescripcion,
    ep.Color               AS EstadoColor,
    p.FechaCreacion
  FROM bc_proceso p
  LEFT JOIN {$wpdb->prefix}users u ON u.ID = p.IdUserCreation
  LEFT JOIN bc_estado_proceso ep ON ep.Id = p.IdEstadoProceso
  LEFT JOIN {$tabla_cliente} c
      ON c.ID = p.IdImportador
  {$where_sql}
  ORDER BY p.FechaCreacion DESC
  LIMIT %d OFFSET %d
";

$prepared = $wpdb->prepare( $select_sql, $params );
$procesos = $wpdb->get_results( $prepared );

// Traemos solo los activos y en el orden lógico
$estados = $wpdb->get_results(
  "SELECT Descripcion, Color 
   FROM {$tabla_estados}
   WHERE Activo = 1
   ORDER BY Id"
);

// 1) Capturamos el POST de “Emitir”
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['emitir'], $_POST['process_id'] ) ) {
  $id = intval( $_POST['process_id'] );
  // Verificamos el nonce
  check_admin_referer( 'cambiar_estado_emitido', 'nonce_emitido_' . $id );

  // Buscamos el Id del estado “Emitido”
  $estado_emitido = $wpdb->get_var( 
    $wpdb->prepare(
      "SELECT Id 
         FROM {$tabla_est} 
        WHERE Codigo = %s 
          AND Activo = 1", 
      'EMIT'
    ) 
  );
  if ( $estado_emitido ) {
    // Actualizamos el estado
    $wpdb->update(
      $tabla,
      [ 'IdEstadoProceso' => $estado_emitido ],
      [ 'Id'              => $id ]
    );
  }
  // Redirigimos de nuevo a la misma página para evitar resubmit
  wp_safe_redirect( add_query_arg( $_GET, $_SERVER['REQUEST_URI'] ) );
  exit;
}

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
                <th>Emitir</th>
                <th>Detalle</th>            
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
                  <form method="post" style="display:inline;">
                    <?php wp_nonce_field( 'cambiar_estado_emitido', 'nonce_emitido_' . $p->Id ); ?>
                    <input type="hidden" name="process_id" value="<?= esc_attr( $p->Id ) ?>">
                    <button
                      type="submit"
                      name="emitir"
                      class="btn"
                      style="padding:4px 8px; font-size:0.85em;"
                      title="Marcar como Emitido"
                    >Emitir</button>
                  </form>
                </td>
                <td>
                  <a 
                    href="?view=bitacora_detalle&id=<?= esc_attr($p->Id) ?>" 
                    class="detail-link" 
                    title="Ver detalle"
                  >
                    <svg 
                      width="18" 
                      height="18" 
                      fill="currentColor" 
                      viewBox="0 0 24 24" 
                      aria-hidden="true"
                    >
                      <path 
                        fill-rule="evenodd" 
                        d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 
                          0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 
                          1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 
                          1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 
                          12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 
                          14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 
                          12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 
                          12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 
                          12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" 
                        clip-rule="evenodd" 
                      />
                    </svg>
                  </a>
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