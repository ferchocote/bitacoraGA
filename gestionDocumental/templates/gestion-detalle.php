<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión Detalle</title>
    <link rel="stylesheet" href="/wp-content/bitacoras/styles/style.css">
</head>
<body>

<!-- Toolbar: filtro y nuevo documento -->
<div class="toolbar" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
  <form method="get" action="" class="filter-form" style="flex:1;">
    <input type="hidden" name="view" value="gestion_detalle">
    <input type="hidden" name="id" value="<?= isset($gestionId) ? esc_attr($gestionId) : '' ?>">
    <div class="filter-grid">
      <div class="filter-field full-width input-icon-wrapper">
        <label for="q">Buscar Documento:</label>
        <div class="input-icon-group">
          <input
            type="text"
            id="q"
            name="q"
            value="<?= esc_attr($q ?? '') ?>"
            placeholder="Filtrar por nombre de documento">
          <button type="submit" class="icon-btn" title="Buscar">🔍</button>
        </div>
      </div>
    </div>
  </form>
  <a href="#" id="btn-nuevo-documento" class="btn btn-icon" onclick="showLoader()">
    <svg class="w-[18px] h-[18px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5" />
    </svg>
    Nuevo Documento
  </a>
</div>

<!-- Vista: Detalle de gestión documental -->
<?php if (!empty($mensaje)) echo $mensaje; ?>
<h2>Documentos de la gestión</h2>
<?php if (empty($documentosAgrupados)): ?>
  <p>No hay documentos asociados a esta gestión.</p>
<?php else: ?>
  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>Tipo</th>
          <th>Fecha</th>
          <th>Nombre</th>
          <th>Descripción</th>
          <th>Descargar</th>
          <th>Detalle</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($documentosAgrupados as $tipo => $porFecha): ?>
          <?php foreach ($porFecha as $fecha => $docs): ?>
            <?php foreach ($docs as $doc): ?>
              <tr>
                <td><?= esc_html($doc->TipoGestionDocumental ?? $tipo) ?></td>
                <td class="gd-fecha"
                    data-fecha-documento="<?= esc_attr($doc->FechaDocumento ?? '') ?>"
                    data-fecha-subida="<?= esc_attr($doc->FechaSubida ?? '') ?>">
                  <?php if (!empty($doc->FechaDocumento)): ?>
                    <?= esc_html(date('d/m/Y', strtotime($doc->FechaDocumento))) ?>
                  <?php else: ?>
                    <?= esc_html($doc->FechaSubida ?? '') ?>
                  <?php endif; ?>
                </td>
                <td><?= esc_html($doc->NombreArchivo) ?></td>
                <td><?= isset($doc->Descripcion) ? esc_html($doc->Descripcion) : '' ?></td>
                <td>
                  <a href="/wp-content/gestionDocumental/controllers/descargar_documento.php?id=<?= esc_attr($doc->RutaArchivo) ?>" target="_blank" title="Ver/Descargar" onclick="showLoader()" download>
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                      <path fill-rule="evenodd" d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd" />
                    </svg>
                  </a>
                </td>
                <td>
                  <a href="#" class="btn-detalle-doc"
                     data-tipo="<?= esc_attr($doc->TipoGestionDocumental ?? $tipo) ?>"
                     data-fecha-documento="<?= esc_attr($doc->FechaDocumento ?? '') ?>"
                     data-fecha-subida="<?= esc_attr($doc->FechaSubida ?? '') ?>"
                     data-nombre="<?= esc_attr($doc->NombreArchivo) ?>"
                     data-descripcion="<?= esc_attr($doc->Descripcion ?? '') ?>"
                     data-tipo_doc_conta="<?= esc_attr($doc->TipoDocContabilidad ?? '') ?>"
                     data-tipo_doc_cliente="<?= esc_attr($doc->TipoDocCliente ?? '') ?>"
                     data-nombre_cliente="<?= esc_attr($doc->NombreClienteProveedor ?? '') ?>"
                     data-numero_documento="<?= esc_attr($doc->NumeroDocumento ?? '') ?>"
                     data-ruta_archivo="<?= esc_attr($doc->RutaArchivo) ?>"
                     title="Ver Detalle">
                    Detalle
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php
    $total_pages = ceil($total_docs / $per_page);
    if ($total_pages > 1): ?>
      <div class="pagination">
        <?php if ($page > 1): ?>
          <a href="?view=gestion_detalle&id=<?= esc_attr($gestionId) ?>&paged=<?= $page - 1 ?>">&laquo; Anterior</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <?php if ($i == $page): ?>
            <span class="current"><?= $i ?></span>
          <?php else: ?>
            <a href="?view=gestion_detalle&id=<?= esc_attr($gestionId) ?>&paged=<?= $i ?>"><?= $i ?></a>
          <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
          <a href="?view=gestion_detalle&id=<?= esc_attr($gestionId) ?>&paged=<?= $page + 1 ?>">Siguiente &raquo;</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Popup para nuevo documento -->
<input type="checkbox" id="popup-toggle-add-doc" hidden>
<div class="overlay-add">
  <div class="form-container" style="max-width:700px; margin:auto;">
    <h1>Crear Nuevo Documento</h1>
    <?php if (!empty($mensaje)) echo $mensaje; ?>
    <form id="form-nuevo-documento" method="post" enctype="multipart/form-data" action="">
      <input type="hidden" name="gestion_id" value="<?= isset($gestionId) ? esc_attr($gestionId) : '' ?>">
      <div class="form-grid">
        <div class="form-group full-width">
          <label for="tipo_documento">Tipo de Gestión Documental</label>
          <select class="input" name="tipo_documento" id="tipo_documento" required>
            <option value="">Seleccione...</option>
            <?php foreach ($tiposDocumento as $tipo): ?>
              <option value="<?= esc_attr($tipo->Id) ?>"><?= esc_html($tipo->Nombre) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="tipo_doc_conta">Tipo Documento Contabilidad</label>
          <select class="input" name="tipo_doc_conta" id="tipo_doc_conta">
            <option value="">Seleccione...</option>
            <?php foreach ($tiposDocContabilidad as $tipo): ?>
              <option value="<?= esc_attr($tipo->Id) ?>"><?= esc_html($tipo->Descripcion) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="grid-column: 1 / -1;">
          <label for="cliente_proveedor">Cliente/Proveedor</label>
          <select class="input" name="cliente_proveedor" id="cliente_proveedor">
            <option value="">Seleccione...</option>
            <?php
            $clientes = $wpdb->get_results("SELECT Id, RazonSocial, NumeroDocumento, EsCliente, EsProveedor 
                                          FROM bc_cliente 
                                          WHERE (EsCliente = 1 OR EsProveedor = 1) 
                                          AND Activo = 1 
                                          ORDER BY RazonSocial");
            foreach ($clientes as $cliente): 
              $tipo = [];
              if ($cliente->EsCliente) $tipo[] = 'Cliente';
              if ($cliente->EsProveedor) $tipo[] = 'Proveedor';
              $tipoTexto = implode('/', $tipo);
            ?>
              <option value="<?= esc_attr($cliente->Id) ?>">
                <?= esc_html($cliente->RazonSocial) ?> - <?= esc_html($cliente->NumeroDocumento) ?> (<?= esc_html($tipoTexto) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="fecha_documento">Fecha Documento</label>
          <input class="input" type="date" name="fecha_documento" id="fecha_documento">
        </div>
        <div class="form-group">
          <label for="nombre">Nombre del Archivo</label>
          <input class="input" type="text" name="nombre" id="nombre" required>
        </div>
        <div class="form-group">
          <label for="archivo">Seleccionar Archivo</label>
          <input class="input" type="file" name="archivo" id="archivo">
        </div>
        <div class="form-group full-width">
          <label for="descripcion">Descripción</label>
          <textarea class="input" name="descripcion" id="descripcion" rows="2"></textarea>
        </div>
      </div>
      <div class="form-buttons">
        <label for="popup-toggle-add-doc" class="btn close">Cancelar</label>
        <button type="submit" class="btn">Crear</button>
      </div>
    </form>
  </div>
</div>

<!-- Popup Detalle Documento (solo visual) -->
<input type="checkbox" id="popup-toggle-detalle-doc" hidden>
<div class="overlay-detalle" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); align-items:center; justify-content:center; z-index:9999;">
  <div class="form-container" style="max-width:700px; margin:auto; background:#fff; border-radius:8px; box-shadow:0 2px 16px rgba(0,0,0,0.2);">
    <h1>Detalle Documento</h1>
    <form id="form-detalle-documento" class="form-grid" style="pointer-events:none;">
      <div class="form-group full-width">
        <label>Tipo de Gestión Documental</label>
        <input class="input" type="text" id="detalle_tipo_documento" readonly>
      </div>
      <div class="form-group">
        <label>Tipo Documento Contabilidad</label>
        <input class="input" type="text" id="detalle_tipo_doc_conta" readonly>
      </div>
      <div class="form-group" style="grid-column: 1 / -1;">
        <label>Cliente/Proveedor</label>
        <input class="input" type="text" id="detalle_cliente_proveedor" readonly>
      </div>
      <div class="form-group">
        <label>Fecha Documento</label>
        <input class="input" type="date" id="detalle_fecha_documento" readonly>
      </div>
      <div class="form-group">
        <label>Nombre del Archivo</label>
        <input class="input" type="text" id="detalle_nombre" readonly>
      </div>
      <div class="form-group">
        <label>Descripción</label>
        <textarea class="input" id="detalle_descripcion" rows="2" readonly></textarea>
      </div>
      <div class="form-buttons">
        <label for="popup-toggle-detalle-doc" class="btn close" style="pointer-events:auto;">Cerrar</label>
      </div>
    </form>
  </div>
</div>

<!-- bc: date-utils.js activo (fechas según navegador) | pub 2026-01-27 -->
<script src="/wp-content/bitacoras/assets/js/date-utils.js?v=20260127"></script>

<script>
console.log('documentosAgrupados:', <?php echo json_encode($documentosAgrupados); ?>);
// Normalizar fechas renderizadas (evita desfase UTC vs local)
document.querySelectorAll('.gd-fecha').forEach(td => {
  const fechaDocumento = td.dataset.fechaDocumento || '';
  const fechaSubida = td.dataset.fechaSubida || '';
  if (!fechaDocumento && fechaSubida && typeof bcFormatFechaLocalDate === 'function') {
    td.textContent = bcFormatFechaLocalDate(fechaSubida);
  }
});
// Abrir popup al hacer click en el botón Nuevo Documento
const btnNuevoDoc = document.getElementById('btn-nuevo-documento');
const popupToggle = document.getElementById('popup-toggle-add-doc');
const overlayAdd = document.querySelector('.overlay-add');
if (btnNuevoDoc) {
  btnNuevoDoc.addEventListener('click', function(e) {
    e.preventDefault();
    popupToggle.checked = true;
    overlayAdd.style.display = 'flex';
    hideLoader(); // Oculta el loader al mostrar el popup
  });
}
// Cerrar popup al hacer click en Cancelar o fuera del popup
overlayAdd.addEventListener('click', function(e) {
  if (e.target === overlayAdd) {
    popupToggle.checked = false;
    overlayAdd.style.display = 'none';
  }
});
popupToggle.addEventListener('change', function() {
  overlayAdd.style.display = popupToggle.checked ? 'flex' : 'none';
});
overlayAdd.style.display = 'none';

// Oculta el loader al hacer clic en descargar
const descargarLinks = document.querySelectorAll('a[title="Ver/Descargar"]');
descargarLinks.forEach(link => {
  link.addEventListener('click', function() {
    setTimeout(hideLoader, 500); // Oculta el loader poco después del clic
  });
});

document.getElementById('form-nuevo-documento').addEventListener('submit', function() {
    showLoader(); // Muestra el loader al enviar el formulario
});

// Caso de uso: DetalleDocumentoGestion
const btnDetalleDocs = document.querySelectorAll('.btn-detalle-doc');
console.log('btnDetalleDocs:', btnDetalleDocs);
const popupToggleDetalle = document.getElementById('popup-toggle-detalle-doc');
const overlayDetalle = document.querySelector('.overlay-detalle');

btnDetalleDocs.forEach(btn => {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('detalle_tipo_documento').value = btn.dataset.tipo || '';
    document.getElementById('detalle_tipo_doc_conta').value = btn.dataset.tipo_doc_conta || '';
    document.getElementById('detalle_cliente_proveedor').value = btn.dataset.nombre_cliente || '';
    const fechaDoc = btn.dataset.fechaDocumento || '';
    const fechaSubida = btn.dataset.fechaSubida || '';
    if (fechaDoc) {
      document.getElementById('detalle_fecha_documento').value = fechaDoc;
    } else if (fechaSubida && typeof bcLocalISODate === 'function') {
      document.getElementById('detalle_fecha_documento').value = bcLocalISODate(fechaSubida);
    } else {
      document.getElementById('detalle_fecha_documento').value = '';
    }
    document.getElementById('detalle_nombre').value = btn.dataset.nombre || '';
    document.getElementById('detalle_descripcion').value = btn.dataset.descripcion || '';
    popupToggleDetalle.checked = true;
    overlayDetalle.style.display = 'flex';
    hideLoader();
  });
});

// Cerrar popup al hacer click fuera del popup
overlayDetalle.addEventListener('click', function(e) {
  if (e.target === overlayDetalle) {
    popupToggleDetalle.checked = false;
    overlayDetalle.style.display = 'none';
  }
});
popupToggleDetalle.addEventListener('change', function() {
  overlayDetalle.style.display = popupToggleDetalle.checked ? 'flex' : 'none';
});

// Inicialización del overlay (mover al final del script)
overlayDetalle.style.display = 'none'; // Esta línea debe ir después de todas las definiciones
</script>

</body>
</html>
