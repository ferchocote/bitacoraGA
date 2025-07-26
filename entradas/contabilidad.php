<?php
    // Incluye WordPress para usar $wpdb
    require_once('../../../wp-load.php');

    global $wpdb;

    // Consulta los tipos de documento
    $tipoIdentificacion = $wpdb->get_results("SELECT * FROM bc_tipo_documento");
    $tipoContabilidad = $wpdb->get_results("SELECT * FROM bc_tipo_documento_contabilidad");

    // Genera el HTML del select
    ?>

<div>
    <h3 id="popup-title">Detalle Contabilidad</h3>

    <form id="entrada-form" class="form-grid">     
        <input type="hidden" id="Id" name="id" />
        <input type="hidden" id="IdEntradaBitacora" name="idEntradaBitacora" />

        <div class="form-row">
            <label>Descripción</label>
            <textarea id="Descripcion" name="descripcion" rows="4" style="resize: vertical; width: 100%;" required></textarea>
        </div>
        <div class="form-row">
            <label for="NombreClienteProveedor">Nombre Cliente Proveedor</label>
            <input type="text" id="NombreClienteProveedor" name="NombreClienteProveedor" />
        </div>
        <div class="form-row">
            <label for="FechaDocumento">Fecha Documento</label>
            <input type="date" id="FechaDocumento" name="FechaDocumento" />
        </div>
        <div class="form-row">
            <label for="FechaIngresoSistema">Fecha Ingreso Sistema</label>
            <input type="date" id="FechaIngresoSistema" name="FechaIngresoSistema" />
        </div>
        <div class="form-row">
            <label for="FechaVencimiento">Fecha Vencimiento</label>
            <input type="date" id="FechaVencimiento" name="FechaVencimiento" />
        </div>
        <div class="form-group">
            <label>Tipo Documento</label>
            <select id="IdTipoDocumento" name="IdTipoDocumento" required>
                <option value="">Seleccione...</option>
                <?php foreach ($tipoIdentificacion as $tipo): ?>
                    <option value="<?= esc_attr($tipo->Id) ?>">
                        <?= esc_html($tipo->Descripcion) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
             <div class="form-group">
            <label>Tipo Documento Contabilidad</label>
            <select id="IdTipoDocumentoContabilidad" name="IdTipoDocumentoContabilidad" required>
                <option value="">Seleccione...</option>
                <?php foreach ($tipoContabilidad as $tipo): ?>
                    <option value="<?= esc_attr($tipo->Id) ?>">
                        <?= esc_html($tipo->Descripcion) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        </div>
       

    </form>
    <div style="margin-top: 10px;">
        <label for="popup-toggle" class="btn">Cerrar</label>
        <button type="button" id="btn-guardar" class="btn" style="display: none;">Guardar</button>
    </div>
</div>