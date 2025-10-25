<!-- Vista: Listado de importadores con gestión documental -->
<div class="toolbar" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
  <form method="get" action="" class="filter-form" style="flex:1;">
    <input type="hidden" name="view" value="gestion_documental">
    <div class="filter-grid">
      <div class="filter-field full-width input-icon-wrapper">
        <label for="q">Buscar Importador:</label>
        <div class="input-icon-group">
          <input
            type="text"
            id="q"
            name="q"
            value="<?= esc_attr($q ?? '') ?>"
            placeholder="Filtrar por nombre de importador">
          <button type="submit" class="icon-btn" title="Buscar">🔍</button>
        </div>
      </div>
    </div>
  </form>
  <a href="?view=gestion_documental&action=nueva" class="btn btn-icon" onclick="showLoader()">
    <svg class="w-[18px] h-[18px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5" />
    </svg>
    Nueva Gestión
  </a>
</div>

<?php if (empty($importadores)): ?>
  <p>No hay importadores con gestión documental.</p>
<?php else: ?>
  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>Importador</th>
          <th>Nit/Numero Documento</th>
          <th>Documentos</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($importadores as $imp): ?>
          <tr>
            <td><?= esc_html($imp->RazonSocial) ?></td>
            <td><?= esc_html($imp->NumeroDocumento ?? $imp->Nit ?? '') ?></td>
            <td>
              <a href="?view=gestion_detalle&id=<?= esc_attr($imp->GestionID) ?>" class="detail-link" title="Ver gestión" onclick="showLoader()">
                <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path fill-rule="evenodd" d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd" />
                </svg>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php
    $total_pages = ceil($total_gestiones / $per_page);
    if ($total_pages > 1): ?>
      <div class="pagination">
        <?php if ($page > 1): ?>
          <a href="?view=gestion_documental&paged=<?= $page - 1 ?>">&laquo; Anterior</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <?php if ($i == $page): ?>
            <span class="current"><?= $i ?></span>
          <?php else: ?>
            <a href="?view=gestion_documental&paged=<?= $i ?>"><?= $i ?></a>
          <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
          <a href="?view=gestion_documental&paged=<?= $page + 1 ?>">Siguiente &raquo;</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
<?php endif; ?>
