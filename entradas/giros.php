<div >
        <h3 id="popup-title">Detalle Giros</h3>

        <form id="cliente-form" class="form-grid">
            <input type="hidden" id="cliente-id" name="id" />
            

            <div class="form-row">
                <label>Documento Transporte</label>
                <input type="text" id="cliente-doc" name="documento" />
            </div>
            <div class="form-row">
                <label>Razón Social</label>
                <input type="text" id="cliente-razon" name="razon_social" />
            </div>
            <div class="form-row">
                <label>Dirección</label>
                <input type="text" id="cliente-dir" name="direccion" />
            </div>
            <div class="form-row">
                <label>Teléfono</label>
                <input type="text" id="cliente-cel" name="celular" />
            </div>
            <div class="form-row">
                <label>Correo Electrónico</label>
                <input type="email" id="cliente-correo" name="correo" />
            </div>
            <div class="form-row">
                <label>Actividad Economica</label>
                <input type="text" id="cliente-ActividadEconomica" name="ActividadEconomica" />
            </div>
            <div class="form-row">
                <label>Responsable Iva</label>
                <input type="checkbox" id="cliente-ResponsableIva" name="ResponsableIva" />
            </div>
            <div class="form-row">
                <label>Aplica Retenciones</label>
                <input type="checkbox" id="cliente-AplicaRetenciones" name="AplicaRetenciones" />
            </div>

        </form>
        <div style="margin-top: 10px;">
            <label for="popup-toggle" class="btn">Cerrar</label>
            <button type="button" id="btn-guardar" class="btn" style="display: none;">Guardar</button>
        </div>
    </div>
    