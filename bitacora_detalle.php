  <?php
  define('WP_USE_THEMES', false);
  require_once('../../wp-load.php');


  if (!is_user_logged_in()) {
    wp_redirect(wp_login_url($_SERVER['REQUEST_URI']));
    exit;
  }

  global $wpdb;
  // Leer el ID del proceso que viene por URL
  $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
  if (!$id) {
    echo '<p>Proceso no válido.</p>';
    return;
  }

  // Tablas
  $tabla = 'bc_' . 'proceso';
  $tabla_clientes = 'bc_' . 'cliente';
  $tabla_estados = 'bc_' . 'estado_proceso';
  $tabla_detalle = 'bc_' . 'detalle_proceso';

  $bitacoras = $wpdb->get_results("SELECT * FROM wp_users ");

  // Consultar datos del proceso (incluye creador, cliente, importador y estado)
  $sql = $wpdb->prepare(
    "SELECT
          p.*, 
          u.user_login AS creador,
          c1.RazonSocial AS Cliente,
          c2.RazonSocial AS Importador,
          ep.Descripcion AS EstadoDescripcion,
          ep.Color       AS EstadoColor
      FROM {$tabla} p
      LEFT JOIN {$wpdb->prefix}users u
        ON u.ID = p.IdUserCreation
      LEFT JOIN {$tabla_clientes} c1
        ON c1.Id = p.IdCliente
      LEFT JOIN {$tabla_clientes} c2
        ON c2.Id = p.IdImportador
      LEFT JOIN {$tabla_estados} ep
        ON ep.Id = p.IdEstadoProceso
      WHERE p.Id = %d",
    $id
  );
  $proceso = $wpdb->get_row($sql);
  if (!$proceso) {
    echo '<p>Proceso no encontrado.</p>';
    return;
  }

  // Detalles relacionados
  $detalles = $wpdb->get_results(
    $wpdb->prepare(
      "SELECT * FROM {$tabla_detalle} WHERE IdProceso = %d ORDER BY Id",
      $id
    )
  );

  // Listas para selects
  $clientes = $wpdb->get_results("SELECT Id, RazonSocial FROM {$tabla_clientes} WHERE Activo=1 ORDER BY RazonSocial");
  $importadores = $clientes; // mismos registros, diferencia según flujo
  $estadosList = $wpdb->get_results("SELECT Id, Descripcion FROM {$tabla_estados} WHERE Activo=1 ORDER BY Id");
  ?>

  <!DOCTYPE html>
  <h1>Entradas Bitácora</h1>

  <!-- Sección de resumen: una sola tarjeta con múltiples datos -->
  <input type="checkbox" id="popup-toggle-edit" hidden>
  <div class="summary-card single">

    <label for="popup-toggle-edit" class="edit-icon" title="Editar Proceso">⚙️</label>
    <h2>Información del Proceso</h2>
    <div class="summary-grid">
      <div><strong>DO:</strong> <?= esc_html($proceso->DO) ?></div>
      <div><strong>Cliente:</strong> <?= esc_html($proceso->Cliente) ?></div>
      <div><strong>Importador:</strong> <?= esc_html($proceso->Importador) ?></div>
      <div><strong>Estado:</strong> <span class="status-label" style="background-color: <?= esc_attr($proceso->EstadoColor) ?>;"><?= esc_html($proceso->EstadoDescripcion) ?></span></div>
      <div><strong>Creado el:</strong> <?= date('d/m/Y', strtotime($proceso->FechaCreacion)) ?></div>
      <div><strong>Creador:</strong> <?= esc_html($proceso->creador) ?></div>
      <div><strong>Tipo Proceso:</strong> <?= esc_html($proceso->TipoProceso) ?></div>
      <div><strong>DO Agencia:</strong> <?= esc_html($proceso->DOAgencia) ?></div>
      <div><strong>Agente Carga:</strong> <?= esc_html($proceso->AgenteCarga) ?></div>
      <div><strong>ETA:</strong> <?= date('d/m/Y', strtotime($proceso->ETA)) ?></div>
      <div><strong>Días Libres:</strong> <?= esc_html($proceso->DiasLibres) ?></div>
      <div><strong>Digitación/Revision:</strong> <?= esc_html($proceso->DigitacionRevision) ?></div>
    </div>
  </div>


  <input type="checkbox" id="popup-toggle-add" hidden>

  <div class="toolbar" style="display:flex; justify-content:flex-end; gap:10px; margin-bottom:20px;">
    <label for="popup-toggle-add" class="btn">➕ Nueva Entrada</label>
  </div>

  <!-- Popup de edición/formulario completo -->
  <div class="overlay-edit">
    <div class="modal-container">
      <h3>Editar Proceso</h3>
      <form method="post" action="?view=editar_proceso&id=<?= esc_attr($proceso->Id) ?>" class="popup-grid-4">
        <?php wp_nonce_field('editar_proceso_action', 'editar_proceso_nonce'); ?>

        <!-- Campos principales -->
        <input type="hidden" name="detalle_id" value="<?= esc_attr($detalle ? $detalle->Id : '') ?>">
        <div class="form-group"><label for="DO">DO:</label>
          <input readonly type="text" id="DO" name="DO" value="<?= esc_attr($proceso->DO) ?>">
        </div>
        <div class="form-group"><label for="IdEmpresa">Cliente:</label>
          <select id="IdEmpresa" name="IdEmpresa"><?php foreach ($clientes as $c): ?><option value="<?= $c->Id ?>" <?= selected($proceso->IdEmpresa, $c->Id, false) ?>><?= esc_html($c->RazonSocial) ?></option><?php endforeach; ?></select>
        </div>
        <div class="form-group"><label for="IdImportador">Importador:</label>
          <select id="IdImportador" name="IdImportador"><?php foreach ($importadores as $imp): ?><option value="<?= $imp->Id ?>" <?= selected($proceso->IdImportador, $imp->Id, false) ?>><?= esc_html($imp->RazonSocial) ?></option><?php endforeach; ?></select>
        </div>
        <div class="form-group"><label for="IdEstadoProceso">Estado:</label>
          <select id="IdEstadoProceso" name="IdEstadoProceso"><?php foreach ($estadosList as $st): ?><option value="<?= $st->Id ?>" <?= selected($proceso->IdEstadoProceso, $st->Id, false) ?>><?= esc_html($st->Descripcion) ?></option><?php endforeach; ?></select>
        </div>

        <div class="form-group"><label for="FechaCreacion">Fecha Creación:</label>
          <input readonly type="date" id="FechaCreacion" name="FechaCreacion" value="<?= esc_attr(date('Y-m-d', strtotime($proceso->FechaCreacion))) ?>">
        </div>

        <!-- Campos adicionales -->
        <div class="form-group"><label for="TipoProceso">Tipo Proceso:</label>
          <input type="text" id="TipoProceso" name="TipoProceso" value="<?= esc_attr($proceso->TipoProceso) ?>">
        </div>
        <div class="form-group"><label for="DOAgencia">DO Agencia:</label>
          <input type="text" id="DOAgencia" name="DOAgencia" value="<?= esc_attr($proceso->DOAgencia) ?>">
        </div>
        <div class="form-group"><label for="AgenteCarga">Agente Carga:</label>
          <input type="text" id="AgenteCarga" name="AgenteCarga" value="<?= esc_attr($proceso->AgenteCarga) ?>">
        </div>
        <div class="form-group"><label for="ETA">ETA:</label>
          <input type="datetime-local" id="ETA" name="ETA" value="<?= esc_attr(date('Y-m-d\TH:i', strtotime($proceso->ETA))) ?>">
        </div>

        <!-- Resto de campos -->
        <?php foreach (
          [
            'DiasLibres' => 'DiasLibres',
            'DigitacionRevision' => 'DigitacionRevision',
            'Aduana' => 'Aduana',
            'Producto' => 'Producto',
            'NumeroBL' => 'NumeroBL',
            'Contenedor' => 'Contenedor',
            'Manifiesto' => 'Manifiesto',
            'Pies' => 'Pies',
            'Bulto' => 'Bulto',
            'PesoBruto' => 'PesoBruto',
            'Bandera' => 'Bandera'
          ] as $field => $prop
        ): ?>
          <div class="form-group">
            <label for="<?= $field ?>"><?= $prop ?>:</label>
            <input type="text" id="<?= $field ?>" name="<?= $field ?>" value="<?= esc_attr($proceso->$prop) ?>">
          </div>
        <?php endforeach; ?>

        <!-- Campos de detalle: siempre se muestran -->
        <div class="form-group"><label for="Liberacion">Liberación:</label>
          <input type="datetime-local" id="Liberacion" name="Liberacion" value="<?= esc_attr($detalle ? date('Y-m-d\TH:i', strtotime($detalle->Liberacion)) : '') ?>">
        </div>
        <div class="form-group"><label for="Aceptacion">Aceptación:</label>
          <input type="datetime-local" id="Aceptacion" name="Aceptacion" value="<?= esc_attr($detalle ? date('Y-m-d\TH:i', strtotime($detalle->Aceptacion)) : '') ?>">
        </div>
        <div class="form-group"><label for="Selectividad">Selectividad:</label>
          <input type="datetime-local" id="Selectividad" name="Selectividad" value="<?= esc_attr($detalle ? date('Y-m-d\TH:i', strtotime($detalle->Selectividad)) : '') ?>">
        </div>
        <div class="form-group"><label for="Levante">Levante:</label>
          <input type="datetime-local" id="Levante" name="Levante" value="<?= esc_attr($detalle ? date('Y-m-d\TH:i', strtotime($detalle->Levante)) : '') ?>">
        </div>
        <div class="form-group"><label for="EntregaTransporte">Entrega Transporte:</label>
          <input type="datetime-local" id="EntregaTransporte" name="EntregaTransporte" value="<?= esc_attr($detalle ? date('Y-m-d\TH:i', strtotime($detalle->EntregaTransporte)) : '') ?>">
        </div>
        <div class="form-group"><label for="Pago">Pago:</label>
          <input type="datetime-local" id="Pago" name="Pago" value="<?= esc_attr($detalle ? date('Y-m-d\TH:i', strtotime($detalle->Pago)) : '') ?>">
        </div>
        <div class="form-group"><label for="Puerto">Puerto:</label>
          <input type="text" id="Puerto" name="Puerto" value="<?= esc_attr($detalle ? $detalle->Puerto : '') ?>">
        </div>
        <div class="form-group"><label for="Observaciones">Observaciones:</label>
          <input type="text" id="Observaciones" name="Observaciones" value="<?= esc_attr($detalle ? $detalle->Observaciones : '') ?>">
        </div>

        <div class="form-group last">
          <label for="popup-toggle-edit" class="btn close">Cancelar</label>
          <button type="submit" class="btn">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Overlay y popup de Crear Entrada -->
  <div class="overlay-add">
    <div class="popup-add">
      <h3>Crear Nueva Entrada</h3>
      <form method="post" action="?view=guardar_entrada" enctype="multipart/form-data">
        <div class="popup-grid">
          <!-- Tipo de Entrada -->
          <div class="popup-field">
            <label for="tipoEntrada">Tipo de Entrada</label>
            <input type="text" id="tipoEntrada" name="tipoEntrada" required>
          </div>
          <!-- Descripción -->
          <div class="popup-field">
            <label for="descripcionEntrada">Descripción</label>
            <textarea id="descripcionEntrada" name="descripcionEntrada" rows="3" required></textarea>
          </div>
          <!-- Subir Documentos -->
          <div class="popup-field" style="grid-column: 1 / -1;">
            <label for="documentos">Subir Documentos</label>
            <input type="file" id="documentos" name="documentos[]" multiple>
          </div>
        </div>
        <div class="popup-actions">
          <label for="popup-toggle-add" class="btn close">Cancelar</label>
          <button type="submit" class="btn">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <ul class="tabs">
    <li data-tab="tab-contabilidad" data-tipo="CTB" class="active">Contabilidad</li>
    <li data-tab="tab-giros" data-tipo="GRO">Giros</li>
    <li data-tab="tab-transporte" data-tipo="TRS">Transporte</li>
  </ul>

  <!-- Contenido de cada pestaña -->
  <div id="tab-contabilidad" class="tab-content active" data-tipo="CTB"></div>
  <div id="tab-giros" class="tab-content" data-tipo="GRO"></div>
  <div id="tab-transporte" class="tab-content" data-tipo="TRS"></div>
  </div>


 <?php include_once(__DIR__ . '/bitacora_detalle_filtro.php'); ?>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const tabs = document.querySelectorAll('.tabs li');
      const contents = document.querySelectorAll('.tab-content');

      tabs.forEach(tab => {
        tab.addEventListener('click', () => {
          tabs.forEach(t => t.classList.remove('active'));
          contents.forEach(c => c.classList.remove('active'));

          tab.classList.add('active');
          const tabId = tab.getAttribute('data-tab');
          const tipo = tab.getAttribute('data-tipo');

          const contenedor = document.getElementById(tabId);
          contenedor.classList.add('active');

          if (!contenedor.dataset.loaded && typeof cargarEntradas === 'function') {
            cargarEntradas(tipo,tabId);
            contenedor.dataset.loaded = 'true';
          }
        });
      });

      // Cargar primer tab automáticamente
      const initialTab = document.querySelector('.tabs li.active');
      if (initialTab) initialTab.click();
    });
  </script>