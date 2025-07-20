<div class="popup">
  <h3 id="popup-title">Detalle Giros</h3>

  <form id="giro-form" class="form-grid">
    <input type="hidden" id="giro-id" name="IdEntradaBitacora" />

    <div class="form-row">
      <label for="Descripcion">Descripción</label>
      <input type="text" id="Descripcion" name="Descripcion" />
    </div>
    <div class="form-row">
      <label for="ComprobanteSiigo">Comprobante SIIGO</label>
      <input type="text" id="ComprobanteSiigo" name="ComprobanteSiigo" />
    </div>
    <div class="form-row">
      <label for="FechaElaboracion">Fecha Elaboración</label>
      <input type="date" id="FechaElaboracion" name="FechaElaboracion" />
    </div>
    <div class="form-row">
      <label for="NombreTercero">Nombre Tercero</label>
      <input type="text" id="NombreTercero" name="NombreTercero" />
    </div>
    <div class="form-row">
      <label for="DescripcionMovimiento">Descripción Movimiento</label>
      <input type="text" id="DescripcionMovimiento" name="DescripcionMovimiento" />
    </div>
    <div class="form-row">
      <label for="Debito">Débito</label>
      <input type="number" step="0.01" id="Debito" name="Debito" />
    </div>
    <div class="form-row">
      <label for="DOCruzado">DO Cruzado</label>
      <input type="text" id="DOCruzado" name="DOCruzado" />
    </div>
    <div class="form-row">
      <label for="Estado">Estado</label>
      <input type="text" id="Estado" name="Estado" />
    </div>
    <div class="form-row">
      <label for="DO">DO</label>
      <input type="text" id="DO" name="DO" />
    </div>
    <div class="form-row">
      <label for="NumeroDeclaracion">Número Declaración</label>
      <input type="text" id="NumeroDeclaracion" name="NumeroDeclaracion" />
    </div>
    <div class="form-row">
      <label for="USDFOB">USD FOB</label>
      <input type="number" step="0.01" id="USDFOB" name="USDFOB" />
    </div>
    <div class="form-row">
      <label for="USDDeclaradoConFlete">USD Decl. con Flete</label>
      <input type="number" step="0.01" id="USDDeclaradoConFlete" name="USDDeclaradoConFlete" />
    </div>
    <div class="form-row">
      <label for="USDReal">USD Real</label>
      <input type="number" step="0.01" id="USDReal" name="USDReal" />
    </div>
    <div class="form-row">
      <label for="FechaMovimiento">Fecha Movimiento</label>
      <input type="date" id="FechaMovimiento" name="FechaMovimiento" />
    </div>
    <div class="form-row">
      <label for="Proveedor">Proveedor</label>
      <input type="text" id="Proveedor" name="Proveedor" />
    </div>
  </form>

  <div style="margin-top:10px; text-align:right;">
    <label for="popup-toggle" class="btn">Cerrar</label>
    <button type="button" id="btn-guardar-giro" class="btn" style="display:none;">Guardar</button>
  </div>
</div>

<script>
  document.addEventListener('click', function(e) {
    const detalle = e.target.closest('.detalle-cliente');
    const editar  = e.target.closest('.modificar-cliente');
    if (!detalle && !editar) return;

    // Abrir modal
    document.getElementById('popup-toggle').checked = true;

    // Título y botón
    const modo = detalle ? 'detalle' : 'editar';
    document.getElementById('popup-title').textContent = modo === 'detalle' 
      ? 'Detalle Giros' 
      : 'Editar Giros';
    document.getElementById('btn-guardar-giro').style.display = modo === 'editar' 
      ? 'inline-block' 
      : 'none';

    // Aquí carga tus datos via AJAX o asignándolos a cada campo
    // Ejemplo:
    // const id = e.target.dataset.id;
    // fetch(`/wp-content/bitacoras/giro?do=get&id=${id}`)
    //   .then(res => res.json())
    //   .then(data => {
    //     document.getElementById('giro-id').value = data.Id;
    //     document.getElementById('Descripcion').value = data.Descripcion;
    //     // …el resto de campos…
    //   });
  });
</script>