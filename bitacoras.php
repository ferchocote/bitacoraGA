<?php
// Valida rol de usuario 
if ($usuario->rol_codigo == "RRHH") {
  echo "No tienes permiso para acceder a esta vista.";
  exit;
}

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
    p.DO           LIKE %s
    OR u.user_login LIKE %s
    OR p.NumeroBL    LIKE %s
    OR p.Contenedor  LIKE %s
  )";
  // rellenamos los parámetros con la versión con %…
  array_push($params, $like, $like, $like, $like);
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
      ELSE (p.DiasLibres - DATEDIFF(CURDATE(), DATE(p.FechaCreacion)))
    END AS DiasRestantes
  FROM bc_proceso p
  LEFT JOIN {$wpdb->prefix}users u ON u.ID = p.IdUserCreation
  LEFT JOIN bc_estado_proceso ep ON ep.Id = p.IdEstadoProceso
  LEFT JOIN {$tabla_cliente} c
      ON c.ID = p.IdImportador
  {$where_sql}
  ORDER BY DiasRestantes ASC
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
            placeholder="Filtrar por DO, Usuario, BL o Contenedor">
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
          <?php if ($usuario->rol_codigo === 'ADMIN' || $usuario->rol_codigo === 'IMPOR' || $usuario->rol_codigo === 'TRANS' || $usuario->rol_codigo === 'CLI') : ?>
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
              <?php if ($usuario->rol_codigo === 'ADMIN' || $usuario->rol_codigo === 'IMPOR' || $usuario->rol_codigo === 'TRANS' || $usuario->rol_codigo === 'CLI') : ?>
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
  if ($total_pages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?view=bitacoras&paged=<?= $page - 1 ?>">&laquo; Anterior</a>
      <?php endif; ?>
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <?php if ($i == $page): ?>
          <span class="current"><?= $i ?></span>
        <?php else: ?>
          <a href="?view=bitacoras&paged=<?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
      <?php if ($page < $total_pages): ?>
        <a href="?view=bitacoras&paged=<?= $page + 1 ?>">Siguiente &raquo;</a>
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
    <?php if (!$is_cliente_custom): ?>
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
      <div id="loader-overlay">
        <div class="spinner"></div>
      </div>
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
    </div>
  </div>
</div>

<div id="loader-overlay">
  <div class="spinner"></div>
</div>

<script>
  // 1) Definimos las transiciones válidas
  const transiciones = {
    'Creado': ['Selectividad Auto', 'Selectividad Fisica'],
    'Selectividad Auto': ['Orden de Retiro'], // si SelectAuto -> Fisica
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
      document.getElementById('IdProceso').value = id;

      // b) Encontramos el estado actual en esa misma fila
      const fila = btn.closest('tr');
      const estadoEl = fila.querySelector('.status-label');
      const estadoActual = estadoEl ? estadoEl.textContent.trim() : '';

      // c) Calculamos las opciones permitidas
      const permitidos = transiciones[estadoActual] || [];

      // d) Filtramos el <select id="NuevoEstado">
      const select = document.getElementById('NuevoEstado');
      Array.from(select.options).forEach(opt => {
        // La primera opción vacía siempre se deja visible
        if (!opt.value) return opt.hidden = false;

        // Mostrar solo si su texto coincide con uno de los permitidos
        opt.hidden = !permitidos.includes(opt.textContent.trim());
      });

      // e) Reiniciamos selección y observación
      select.value = '';
      document.getElementById('ObservacionCambio').value = '';

      // f) Abrimos el modal
      document.getElementById('gestionar-toggle').checked = true;

      // g) Cargar historial de estados
      cargarHistorialEstados(id);
    });
  });

  // Función para cargar historial de estados por proceso
  function cargarHistorialEstados(idProceso) {
    const cont = document.getElementById('historial-estados-container');
    cont.innerHTML = '<div style="text-align:center; color:#888;">Cargando historial...</div>';
    showLoader();
    fetch(`/wp-content/bitacoras/plugins/cliente/entradas-ajax.php?action=historial_estados&id_proceso=${idProceso}`)
      .then(res => res.json())
      .then(data => {
        if (!Array.isArray(data) || data.length === 0) {
          cont.innerHTML = '<em>No hay historial de estados.</em>';
          return;
        }
        cont.innerHTML = `
          <table class="tabla-historial-estados" style="width:100%; margin-top:10px;">
            <thead>
              <tr>
                <th>Estado Anterior</th>
                <th>Estado Nuevo</th>
                <th>Usuario</th>
                <th>Fecha</th>
                <th>Observación</th>
              </tr>
            </thead>
            <tbody>
              ${data.map(est => `
                <tr>
                  <td>${est.estado_anterior || '-'}</td>
                  <td>${est.estado_nuevo || '-'}</td>
                  <td>${est.usuario || '-'}</td>
                  <td>${est.fecha ? new Date(est.fecha).toLocaleString() : '-'}</td>
                  <td>${est.observacion || ''}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        `;
      }).finally(hideLoader)
      .catch(() => {
        cont.innerHTML = '<em>Error al cargar historial.</em>';
      });
  }
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
</script>