<?php
define('WP_USE_THEMES', false);
require_once('../../wp-load.php');

global $wpdb;
$current_user    = wp_get_current_user();
$usuario = $wpdb->get_row("SELECT u.*, r.Nombre AS rol_nombre, r.Codigo AS rol_codigo, ge.Nombre AS grupo_nombre
        FROM wp_users u
        LEFT JOIN bc_user_role ur ON ur.IdUser = u.ID
        LEFT JOIN bc_roles r ON r.Id = ur.IdRol
        LEFT JOIN bc_grupo_empresa ge ON ge.Id = u.IdAliado
        WHERE u.id = {$current_user->ID}");

// Valida rol de usuario 
if ($usuario && $usuario->rol_codigo == "RRHH") {
  echo "No tienes permiso para acceder a esta vista.";
  exit;
}

$current_user_id = get_current_user_id();
$es_admin_bitacora = ($usuario && isset($usuario->rol_codigo) && $usuario->rol_codigo === 'ADMIN');

$q    = '';
$searchTerm    = '';
$where_clauses = [];
$params        = [];

// Nombre real de tu tabla, ajusta el prefijo si es necesario:
$tabla = 'bc_' . 'proceso';
$tabla_estados = 'bc_' . 'estado_proceso';
$tabla_grupo_empresa = 'bc_' . 'grupo_empresa';
$tabla_cliente = 'bc_' . 'cliente';


// 1) Recuperamos el Id interno del rol “CLIENTE”
$tabla_roles     = 'bc_roles';           // o "{$wpdb->prefix}bc_rol" si usas prefijo
$cliente_rol_id  = (int) $wpdb->get_var("
    SELECT Id 
    FROM {$tabla_roles} 
    WHERE Codigo = 'CLI'
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

if (! empty($_GET['q'])) {
  // 1) el valor limpio para mostrar
  $searchTerm = sanitize_text_field($_GET['q']);
  // 2) la versión con % para la consulta
  $like = '%' . $wpdb->esc_like($searchTerm) . '%';

  $where_clauses[] = "(
    p.DO             LIKE %s
    OR u.user_login  LIKE %s
    OR p.NumeroBL    LIKE %s
    OR p.Contenedor  LIKE %s
    OR ep.Descripcion LIKE %s
    OR c.RazonSocial LIKE %s
  )";
  // rellenamos los parámetros con la versión con %…
  array_push($params, $like, $like, $like, $like, $like, $like);
    $q = $searchTerm;
}

if ($is_cliente_custom) {
  // IDs de cliente relacionados al usuario actual
  $cliente_ids = $wpdb->get_col(
    $wpdb->prepare(
      "SELECT IdCliente 
       FROM bc_cliente_empresa 
       WHERE IdUser = %d",
      $current_user_id
    )
  );

  // Limpieza y normalización
  $cliente_ids = array_map('intval', array_unique(array_filter($cliente_ids)));

  if (!empty($cliente_ids)) {
    $ph = implode(',', array_fill(0, count($cliente_ids), '%d'));
    // Solo por IdCliente
    $where_clauses[] = "p.IdCliente IN ($ph)";
    // OJO: agregamos los IDs UNA VEZ
    $params = array_merge($params, $cliente_ids);
  } else {
    // Sin relación => sin resultados
    $where_clauses[] = "1=0";
  }
} elseif (!($usuario->rol_codigo === 'ADMIN' && $usuario->grupo_nombre === 'GA')) {
  // Filtrar por grupo empresa (IdAliado) para todos excepto ADMIN de GA
  $where_clauses[] = "(c.IdAliado = %d OR imp.IdAliado = %d)";
  $params[] = (int) $usuario->IdAliado;
  $params[] = (int) $usuario->IdAliado;
}


$where_sql = $where_clauses
  ? 'WHERE ' . implode(' AND ', $where_clauses)
  : '';

// 1) Conteo total
$count_sql = "
  SELECT COUNT(*)
  FROM bc_proceso p
  LEFT JOIN {$wpdb->prefix}users u ON u.ID = p.IdUserCreation
  LEFT JOIN {$tabla_cliente} c ON c.ID = p.IdCliente
  LEFT JOIN {$tabla_cliente} imp ON imp.ID = p.IdImportador
  LEFT JOIN bc_estado_proceso ep ON ep.Id = p.IdEstadoProceso
  {$where_sql}
";
// Si no hay placeholders, no llamamos a prepare()
if (! empty($params)) {
  $total = intval($wpdb->get_var($wpdb->prepare($count_sql, $params)));
} else {
  $total = intval($wpdb->get_var($count_sql));
}

// 3) Consulta paginada
$per_page = 10;
$page     = max(1, intval($_GET['paged'] ?? 1));
$offset   = ($page - 1) * $per_page;

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
    p.FechaCreacion,
    p.ETA,
    CASE 
      WHEN p.ETA IS NULL OR p.ETA = '' THEN 0
      ELSE (p.DiasLibres - DATEDIFF(CURDATE(), DATE(p.ETA)))
    END AS DiasRestantes
  FROM bc_proceso p
  LEFT JOIN {$wpdb->prefix}users u ON u.ID = p.IdUserCreation
  LEFT JOIN {$tabla_cliente} c ON c.ID = p.IdCliente
  LEFT JOIN {$tabla_cliente} imp ON imp.ID = p.IdImportador
  LEFT JOIN bc_estado_proceso ep ON ep.Id = p.IdEstadoProceso
  {$where_sql}
  ORDER BY (ep.Codigo = 'COM') ASC, DiasRestantes ASC
  LIMIT %d OFFSET %d
";

$prepared = $wpdb->prepare($select_sql, $params);
$procesos = $wpdb->get_results($prepared);

// Traemos solo los activos y en el orden lógico
$estados = $wpdb->get_results(
  "SELECT Id, Descripcion, Color 
   FROM {$tabla_estados}
   WHERE Activo = 1
   ORDER BY Id"
);
$Listestados = $estados;

// Eliminamos “Creado”
$estadosList = array_filter($estados, function ($e) {
  return $e->Descripcion !== 'Creado';
});

// 1) Capturar el POST de “gestionar”
if (
  $_SERVER['REQUEST_METHOD'] === 'POST' &&
  ! empty($_POST['gestionar_nonce'])
) {
  // 1.1) Verifica el nonce
  check_admin_referer('gestionar_proceso', 'gestionar_nonce');

  // 1.2) Recoge datos
  $id     = intval($_POST['IdProceso']);
  $nuevo  = intval($_POST['NuevoEstado']);
  $obs    = sanitize_text_field($_POST['ObservacionCambio']);

  // 1.3) Obtén el estado actual antes de cambiarlo
  $ant = $wpdb->get_var(
    $wpdb->prepare(
      "SELECT IdEstadoProceso FROM bc_proceso WHERE Id = %d",
      $id
    )
  );

  // 1.4) Actualiza bc_proceso (usa el nombre real de tu tabla)
  $wpdb->update(
    'bc_proceso',
    ['IdEstadoProceso' => $nuevo],
    ['Id'               => $id]
  );

  // 1.5) Inserta en el histórico
  $wpdb->insert(
    'bc_proceso_estado_historial',
    [
      'IdProceso'        => $id,
      'EstadoAnteriorId' => $ant,
      'EstadoNuevoId'    => $nuevo,
      'Observacion'      => $obs,
      'IdUsuarioCambio'  => get_current_user_id(),
      'FechaCambio'      => current_time('mysql')
    ]
  );

  $url = remove_query_arg(
    ['gestionar_nonce', 'NuevoEstado', 'ObservacionCambio', 'IdProceso'],
    wp_unslash($_SERVER['REQUEST_URI'])
  );
  wp_safe_redirect($url);
  exit;
}
?>
<script src="/wp-content/bitacoras/assets/js/common-loader.js"></script>
<!DOCTYPE html>
<style>
  .historial-actions .icon-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    padding: 0;
    line-height: 1;
    font-size: 0;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background: #fff;
    background-repeat: no-repeat;
    background-position: center;
    background-size: 16px 16px;
    color: #0f172a;
    cursor: pointer;
    transition: background 0.15s ease, transform 0.1s ease;
  }
  .historial-actions .icon-action:hover {
    background: #f3f4f6;
    transform: translateY(-1px);
  }
  .historial-actions .icon-action svg {
    width: 16px;
    height: 16px;
    fill: currentColor;
    stroke: currentColor;
    display: block;
  }
  .historial-actions .icon-edit {
    color: #d7ab00;
    background-image: none;
  }
  .historial-actions .icon-delete {
    color: #dc2626;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%23dc2626'%3E%3Cpath d='M7.5 3 6.5 4h-3v2h13V4h-3l-1-1h-5Zm-2 5v8a2 2 0 0 0 2 2h5a2 2 0 0 0 2-2V8h-9Z'/%3E%3C/svg%3E");
  }
</style>

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
            value="<?= esc_attr($q) ?>"
          placeholder="Filtrar por DO, Usuario, BL, Contenedor o Estado">
          <button type="submit" class="icon-btn" title="Buscar">🔍</button>
        </div>
      </div>
    </div>
  </form>
  <?php if ($usuario->rol_codigo === 'ADMIN' || $usuario->rol_codigo === 'IMPOR') : ?>
    <a href="?view=nueva_bitacora" class="btn btn-icon">
      <svg class="w-[18px] h-[18px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5" />
      </svg>
      Nuevo Registro
    </a>
  <?php endif; ?>
</div>

<?php if (empty($procesos)): ?>
  <p>No hay bitácoras registradas.</p>
<?php else: ?>
  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>Fecha de Creación</th>
          <th>DO</th>
          <th>Encargado</th>
          <th>Importador</th>
          <th>Numero BL</th>
          <!-- <th>Contenedor</th> -->
          <th>Días Libres</th>
          <th>ETA</th>
          <th><span style="width: 120px; display:block">Estado</span></th>
          <?php if ($usuario->rol_codigo === 'ADMIN' || $usuario->rol_codigo === 'IMPOR' || $usuario->rol_codigo === 'TRANS' || $usuario->rol_codigo === 'CLI' || $usuario->rol_codigo === 'PAG') : ?>
            <th>Gestionar</th>
          <?php endif; ?>
          <th>Detalle</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($procesos)): ?>
          <tr>
            <td colspan="7">No hay procesos registrados.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($procesos as $p): ?>
            <tr>
              <td><?= esc_html(date('d/m/Y', strtotime($p->FechaCreacion))) ?></td>
              <td><?= esc_html($p->DO) ?></td>
              <td><?= esc_html($p->creador) ?></td>
              <td><?= esc_html($p->RazonSocial) ?></td>
              <td><?= esc_html($p->NumeroBL) ?></td>
              <!-- <td><?= esc_html($p->Contenedor) ?></td> -->
              <td>
                <?php $dias = (int)$p->DiasRestantes; ?>
                <span class="days-chip <?= $dias < 0 ? 'neg' : '' ?>">
                  <?= $dias ?>
                </span>
              </td>
              <td><?= esc_html(date('d/m/Y', strtotime($p->ETA))) ?></td>
              <td>
                <span class="status-label status-<?= strtolower($p->EstadoCodigo) ?>">
                  <?= esc_html($p->EstadoDescripcion) ?>
                </span>
              </td>

              <?php if ($usuario->rol_codigo === 'ADMIN' || $usuario->rol_codigo === 'IMPOR' || $usuario->rol_codigo === 'TRANS' || $usuario->rol_codigo === 'CLI' || $usuario->rol_codigo === 'PAG') : ?>
                <td class="col-gestion">
                  <label
                    for="gestionar-toggle"
                    class="gestionar-btn manage-link"
                    data-id="<?= esc_attr($p->Id) ?>"
                    title="Gestionar Estado"
                    style="cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                    <!-- tu SVG de tres puntitos -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                      <path d="M3 9a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 
                              0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 
                              0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z" />
                    </svg>
                  </label>
                  <?php if ($usuario->rol_codigo === 'ADMIN' || $usuario->rol_codigo === 'CLI') : ?>
                  <a
                    href="javascript:void(0);"
                    class="btn-icon ver-docs-cliente"
                    data-proceso="<?= esc_attr($p->Id) ?>"
                    title="Documentos"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M10 4h-6c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 
                              0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2zM4 6h4.17l2 2H20v10H4V6z"/>
                    </svg>
                  </a>
                   <?php endif; ?>
                </td>
              <?php endif; ?>
              <td class="col-detalle">
                <a
                  href="?view=bitacora_detalle&id=<?= esc_attr($p->Id) ?>"
                  class="detail-link"
                  title="Ver detalle">
                  <svg
                    width="18"
                    height="18"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true">
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
                      clip-rule="evenodd" />
                  </svg>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php
  // 3) Renderizado del paginador
  $total_pages = ceil($total / $per_page);
  if ($total_pages > 1):
    $pagination_query_args = [];
    if (!empty($_GET)) {
      foreach ($_GET as $key => $value) {
        if ($key === 'paged' || is_array($value)) {
          continue;
        }
        $pagination_query_args[$key] = sanitize_text_field(wp_unslash($value));
      }
    }
    if (!isset($pagination_query_args['view'])) {
      $pagination_query_args['view'] = 'bitacoras';
    }
    if ($q !== '') {
      $pagination_query_args['q'] = $q;
    } else {
      unset($pagination_query_args['q']);
    }
    ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <?php
          $prev_query = $pagination_query_args;
          $prev_query['paged'] = $page - 1;
        ?>
        <a href="<?= esc_url('?' . http_build_query($prev_query)) ?>">&laquo; Anterior</a>
      <?php endif; ?>
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <?php
          $page_query = $pagination_query_args;
          $page_query['paged'] = $i;
        ?>
        <?php if ($i == $page): ?>
          <span class="current"><?= $i ?></span>
        <?php else: ?>
          <a href="<?= esc_url('?' . http_build_query($page_query)) ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
      <?php if ($page < $total_pages): ?>
        <?php
          $next_query = $pagination_query_args;
          $next_query['paged'] = $page + 1;
        ?>
        <a href="<?= esc_url('?' . http_build_query($next_query)) ?>">Siguiente &raquo;</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- 4. Sección de leyenda debajo de la tabla -->
  <h2 class="legend-title">Estados</h2>
  <div class="legend-container">
    <?php foreach ($estados as $st): ?>
      <div class="legend-item">
        <span
          class="legend-box"
          style="background-color: <?= esc_attr($st->Color) ?>;"></span>
        <span class="legend-label">
          <?= esc_html($st->Descripcion) ?>
        </span>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<input type="checkbox" id="gestionar-toggle" hidden>



<!-- Overlay / modal de gestión -->
<div class="overlay-manage">
  <div class="popup-manage" style="min-width:800px; max-width:1100px; width:100%;">
    <h3>Gestionar Estado</h3>
    <?php if ($usuario->rol_codigo !== 'CLI' && $usuario->rol_codigo !== 'PAG'): ?>
      <form id="form-gestionar" method="post" action="" onsubmit="showLoader()">
        <?php wp_nonce_field('gestionar_proceso', 'gestionar_nonce'); ?>
        <input type="hidden" name="IdProceso" id="IdProceso">

        <div class="form-group">
          <label for="NuevoEstado">Nuevo Estado:</label>
          <select name="NuevoEstado" id="NuevoEstado" required>
            <option value="">— Seleccione —</option>
            <?php foreach ($estadosList as $st): ?>
              <option value="<?= esc_attr($st->Id) ?>">
                <?= esc_html($st->Descripcion) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="ObservacionCambio">Observación (opcional):</label>
          <textarea name="ObservacionCambio" id="ObservacionCambio" rows="2"></textarea>
        </div>

        <div class="popup-actions">
          <!-- este label desmarca el checkbox y cierra el modal -->
          <label for="gestionar-toggle" class="btn close">Cancelar</label>
          <button type="submit" class="btn">Guardar</button>
        </div>
      </form>

    <?php else: ?>
      <div class="popup-actions" style="justify-content: flex-end; margin-bottom: 16px;">
        <label for="gestionar-toggle" class="btn close">Cancelar</label>
      </div>
    <?php endif; ?>
    <!-- Tabla de historial de estados -->
    <div style="margin-top: 32px;">
      <h4>Historial de Estados</h4>
      <div style="overflow-x:auto;">
        <div id="historial-estados-container">
          <div style="text-align:center; color:#888;">Cargando historial...</div>
        </div>
      </div>
      <?php if ($es_admin_bitacora): ?>
        <div id="historial-editor" style="display:none; margin-top:16px; border-top:1px solid #e5e7eb; padding-top:16px;">
          <h4>Editar historial</h4>
          <input type="hidden" id="HistorialEditId">
          <div class="form-group">
            <label for="HistorialEstadoNuevo">Estado nuevo</label>
            <select id="HistorialEstadoNuevo">
              <option value="">-- Seleccione --</option>
              <?php foreach ($Listestados as $st): ?>
                <option value="<?= esc_attr($st->Id) ?>"><?= esc_html($st->Descripcion) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="HistorialObservacion">Observación</label>
            <textarea id="HistorialObservacion" rows="2"></textarea>
          </div>
          <div class="popup-actions">
            <button type="button" class="btn" id="btn-cancelar-edit-historial" style="background:#dc3545; color:#fff; border-color:#dc3545;">Cancelar</button>
            <button type="button" class="btn" id="btn-guardar-edit-historial">Actualizar</button>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div id="loader-overlay">
      <div class="spinner"></div>
    </div>
  </div>
</div>

<div id="loader-overlay">
  <div class="spinner"></div>
</div>

<div id="modal-docs-cliente" class="modal" style="display:none;">
  <div class="modal-box">
    <div class="modal-header">
      <h3>Documentos</h3>
      <button type="button" id="modal-docs-cliente-cerrar" class="btn close">Cerrar</button>
    </div>
    <div id="lista-docs-cliente" style="margin-top:16px;"></div>
  </div>
</div>

<script>
  const ES_ADMIN_BITACORA = <?= $es_admin_bitacora ? 'true' : 'false' ?>;
  let historialProcesoActual = null;
  let historialData = [];

  function escapeHtml(text) {
    if (!text) return '';
    return text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // 1) Definimos las transiciones válidas
  const transiciones = {
    'Creado': ['Selectividad Auto', 'Selectividad Fisica'],
    'Selectividad Auto': ['Orden de Retiro'],
    'Selectividad Fisica': ['Orden de Retiro'],
    'Orden de Retiro': ['Selectividad Fisica','Transporte'],
    'Transporte': ['Completado'],
  };

  // 2) Para cada botón de gestionar
  document.querySelectorAll('.gestionar-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();

      // a) Guardamos el Id del proceso
      const id = btn.dataset.id;
      const idProcesoInput = document.getElementById('IdProceso');
      if (idProcesoInput) {
        idProcesoInput.value = id;
      }

      // b) Encontramos el estado actual en esa misma fila
      const fila = btn.closest('tr');
      const estadoEl = fila.querySelector('.status-label');
      const estadoActual = estadoEl ? estadoEl.textContent.trim() : '';

      // Reiniciar editor de historial al abrir modal
      limpiarEditorHistorial();

      // c) Calculamos las opciones permitidas
      const permitidos = transiciones[estadoActual] || [];

      // d) Filtramos el <select id="NuevoEstado"> y reiniciamos campos solo si existen (no para clientes)
      const select = document.getElementById('NuevoEstado');
      if (select) {
        Array.from(select.options).forEach(opt => {
          // La primera opción vacía siempre se deja visible
          if (!opt.value) return opt.hidden = false;
          // Mostrar solo si su texto coincide con uno de los permitidos
          opt.hidden = !permitidos.includes(opt.textContent.trim());
        });
        // e) Reiniciamos selección y observación
        select.value = '';
        const obs = document.getElementById('ObservacionCambio');
        if (obs) obs.value = '';
      }

      // f) Abrimos el modal
      document.getElementById('gestionar-toggle').checked = true;

      // g) Cargar historial de estados
      cargarHistorialEstados(id);
    });
  });

  // Función para cargar historial de estados por proceso
  function cargarHistorialEstados(idProceso) {
    historialProcesoActual = idProceso;
    const cont = document.getElementById('historial-estados-container');
    cont.innerHTML = '<div style="text-align:center; color:#888;">Cargando historial...</div>';
    showLoader();
    fetch(`/wp-content/bitacoras/plugins/cliente/entradas-ajax.php?action=historial_estados&id_proceso=${idProceso}`)
      .then(res => res.json())
      .then(data => {
        historialData = Array.isArray(data) ? data : [];
        if (!Array.isArray(data) || data.length === 0) {
          cont.innerHTML = '<em>No hay historial de estados.</em>';
          return;
        }
        const filas = data.map((est, idx) => {
          const esUltimo = idx === 0; // la consulta viene ordenada DESC, primer registro es el último
          const obsEncoded = encodeURIComponent(est.observacion || '');
          const estadoAnteriorEncoded = encodeURIComponent(est.estado_anterior || '');
          return `
            <tr>
              <td>${escapeHtml(est.estado_anterior || '-')}</td>
              <td>${escapeHtml(est.estado_nuevo || '-')}</td>
              <td>${escapeHtml(est.usuario || '-')}</td>
              <td>${est.fecha ? new Date(est.fecha).toLocaleString() : '-'}</td>
              <td class="observacion-cell">${escapeHtml(est.observacion || '')}</td>
              ${ES_ADMIN_BITACORA && esUltimo ? `
                <td class="historial-actions" style="min-width:60px; display:flex; gap:8px;">
                  <button type="button"
                          class="icon-action icon-edit btn-edit-historial"
                          data-id="${est.id}"
                          data-estado-nuevo-id="${est.estado_nuevo_id || ''}"
                          data-estado-anterior-id="${est.estado_anterior_id || ''}"
                          data-estado-anterior="${estadoAnteriorEncoded}"
                          data-observacion="${obsEncoded}"
                          title="Editar estado"
                          aria-label="Editar estado">
                    <svg class="w-[16px] h-[16px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M14 4.182A4.136 4.136 0 0 1 16.9 3c1.087 0 2.13.425 2.899 1.182A4.01 4.01 0 0 1 21 7.037c0 1.068-.43 2.092-1.194 2.849L18.5 11.214l-5.8-5.71 1.287-1.31.012-.012Zm-2.717 2.763L6.186 12.13l2.175 2.141 5.063-5.218-2.141-2.108Zm-6.25 6.886-1.98 5.849a.992.992 0 0 0 .245 1.026 1.03 1.03 0 0 0 1.043.242L10.282 19l-5.25-5.168Zm6.954 4.01 5.096-5.186-2.218-2.183-5.063 5.218 2.185 2.15Z" clip-rule="evenodd" />
                    </svg>
                  </button>
                </td>
              ` : ''}
            </tr>
          `;
        }).join('');
        cont.innerHTML = `
          <table class="tabla-historial-estados" style="width:100%; margin-top:10px;">
            <thead>
              <tr>
                <th>Estado Anterior</th>
                <th>Estado Nuevo</th>
                <th>Usuario</th>
                <th>Fecha</th>
                <th>Observación</th>
                ${ES_ADMIN_BITACORA ? '<th>Acciones</th>' : ''}
              </tr>
            </thead>
            <tbody>
              ${filas}
            </tbody>
          </table>
        `;
        prepararAccionesHistorial();
      })
      .catch(() => {
        cont.innerHTML = '<em>Error al cargar historial.</em>';
      })
      .finally(hideLoader);
  }

  function prepararAccionesHistorial() {
    if (!ES_ADMIN_BITACORA) return;
    document.querySelectorAll('.btn-edit-historial').forEach(btn => {
      btn.addEventListener('click', () => {
        const data = {
          id: parseInt(btn.dataset.id || '0', 10),
          estado_nuevo_id: parseInt(btn.dataset.estadoNuevoId || '0', 10) || '',
          estado_anterior_id: parseInt(btn.dataset.estadoAnteriorId || '0', 10) || '',
          observacion: btn.dataset.observacion ? decodeURIComponent(btn.dataset.observacion) : ''
        };
        const idx = historialData.findIndex(h => parseInt(h.id, 10) === data.id);
        const anteriorRegistro = idx >= 0 && historialData[idx + 1] ? historialData[idx + 1] : null;
        const estadoBase = anteriorRegistro
          ? (anteriorRegistro.estado_nuevo || anteriorRegistro.estado_anterior || '')
          : (btn.dataset.estadoAnterior ? decodeURIComponent(btn.dataset.estadoAnterior) : '');
        abrirEditorHistorial(data, estadoBase);
      });
    });
  }

  function abrirEditorHistorial(data, estadoBase) {
    const editor = document.getElementById('historial-editor');
    const idInput = document.getElementById('HistorialEditId');
    const estadoSelect = document.getElementById('HistorialEstadoNuevo');
    const obsTextarea = document.getElementById('HistorialObservacion');
    if (!editor || !idInput || !estadoSelect || !obsTextarea) return;
    idInput.value = data.id || '';
    estadoSelect.value = data.estado_nuevo_id || '';
    obsTextarea.value = data.observacion || '';
    if (estadoBase) {
      filtrarOpcionesEstado(estadoSelect, estadoBase);
    }
    editor.style.display = 'block';
    editor.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function filtrarOpcionesEstado(selectEl, estadoBase) {
    if (!selectEl) return;
    const permitidos = transiciones[estadoBase] || [];
    Array.from(selectEl.options).forEach(opt => {
      if (!opt.value) {
        opt.hidden = false;
        return;
      }
      opt.hidden = permitidos.length ? !permitidos.includes(opt.textContent.trim()) : true;
    });
  }

  function limpiarEditorHistorial() {
    const editor = document.getElementById('historial-editor');
    const idInput = document.getElementById('HistorialEditId');
    const estadoSelect = document.getElementById('HistorialEstadoNuevo');
    const obsTextarea = document.getElementById('HistorialObservacion');
    if (editor) editor.style.display = 'none';
    if (idInput) idInput.value = '';
    if (estadoSelect) estadoSelect.value = '';
    if (obsTextarea) obsTextarea.value = '';
  }

  function guardarEdicionHistorial() {
    const idInput = document.getElementById('HistorialEditId');
    const estadoSelect = document.getElementById('HistorialEstadoNuevo');
    const obsTextarea = document.getElementById('HistorialObservacion');
    const id = parseInt(idInput?.value || '0', 10);
    const estadoNuevo = parseInt(estadoSelect?.value || '0', 10);
    const observacion = obsTextarea ? obsTextarea.value.trim() : '';
    if (!id || !estadoNuevo) {
      alert('Seleccione un estado y registro para actualizar.');
      return;
    }
    showLoader();
    fetch('/wp-content/bitacoras/plugins/cliente/entradas-ajax.php?action=actualizar_historial_estado', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ id, estado_nuevo_id: estadoNuevo, observacion })
    })
      .then(res => res.json())
      .then(resp => {
        if (resp && resp.success) {
          limpiarEditorHistorial();
          if (resp.proceso_actualizado) {
            // Si cambió el estado del proceso, refrescamos la tabla principal
            window.location.reload();
            return;
          }
          if (historialProcesoActual) cargarHistorialEstados(historialProcesoActual);
        } else {
          alert(resp.message || 'No se pudo actualizar el historial.');
        }
      })
      .catch(() => alert('Error al actualizar historial.'))
      .finally(hideLoader);
  }

  const btnGuardarHistorial = document.getElementById('btn-guardar-edit-historial');
  if (btnGuardarHistorial) {
    btnGuardarHistorial.addEventListener('click', (e) => {
      e.preventDefault();
      guardarEdicionHistorial();
    });
  }
  const btnCancelarHistorial = document.getElementById('btn-cancelar-edit-historial');
  if (btnCancelarHistorial) {
    btnCancelarHistorial.addEventListener('click', (e) => {
      e.preventDefault();
      limpiarEditorHistorial();
    });
  }
  // Cierre del modal principal debe limpiar también el editor
  document.querySelectorAll('label[for="gestionar-toggle"]').forEach(lbl => {
    lbl.addEventListener('click', () => {
      limpiarEditorHistorial();
      const nuevoEstado = document.getElementById('NuevoEstado');
      const obsCambio = document.getElementById('ObservacionCambio');
      if (nuevoEstado) nuevoEstado.value = '';
      if (obsCambio) obsCambio.value = '';
    });
  });
  document.addEventListener('DOMContentLoaded', function() {
    hideLoader();
    // Selecciona todos los enlaces dentro del sidebar (tu menú principal)
    const sidebarLinks = document.querySelectorAll('.toolbar a');

    // Selecciona todos los enlaces dentro del menú desplegable del usuario (hov-menu)
    const userDropdownLinks = document.querySelectorAll('.col-detalle a');

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

    // Aplica la función a los enlaces del menú desplegable del usuario
    addLoaderToLinks(userDropdownLinks);
  });

document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('modal-docs-cliente');
  const modalClose = document.getElementById('modal-docs-cliente-cerrar');
  const contenedorDocs = document.getElementById('lista-docs-cliente');

  document.querySelectorAll('.ver-docs-cliente').forEach(btn => {
    btn.addEventListener('click', () => {
      const idProceso = btn.dataset.proceso;
      contenedorDocs.innerHTML = '<p>Cargando…</p>';
      modal.style.display = 'flex';

      fetch(`/wp-content/bitacoras/plugins/cliente/entradas-ajax.php?action=listar_documentos_cliente&id_proceso=${idProceso}`)
        .then(res => res.json())
        .then(data => {
          if (!Array.isArray(data) || data.length === 0) {
            contenedorDocs.innerHTML = '<em>No hay documentos visibles para este cliente.</em>';
            return;
          }
          contenedorDocs.innerHTML = `
            <table class="tabla-documentos">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>Fecha Cargue</th>
                </tr>
              </thead>
              <tbody>
                ${data.map(doc => `
                  <tr>
                    <td><a href="${doc.url}" target="_blank" download>${doc.nombre}</a></td>
                    <td>${doc.fecha || 'N/A'}</td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          `;
        })
        .catch(err => {
          console.error(err);
          contenedorDocs.innerHTML = '<p>Error cargando documentos.</p>';
        });
    });
  });

  if (modalClose) {
    modalClose.addEventListener('click', () => {
      modal.style.display = 'none';
    });
  }
});

</script>
