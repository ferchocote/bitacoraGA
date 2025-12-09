<?php
    // Incluye WordPress para usar $wpdb
    require_once('../../../wp-load.php');

    global $wpdb;
    $current_user = wp_get_current_user();
    $usuario = $wpdb->get_row("SELECT u.*, r.Nombre AS rol_nombre, r.Codigo AS rol_codigo
            FROM wp_users u
            LEFT JOIN bc_user_role ur ON ur.IdUser = u.ID
            LEFT JOIN bc_roles r ON r.Id = ur.IdRol
            WHERE u.id = {$current_user->ID}");

    // Consulta los tipos de documento y clientes
    $tipoContabilidad = $wpdb->get_results("SELECT * FROM bc_tipo_documento_contabilidad");
    if ($usuario->rol_codigo === 'ADMIN') {
        $clientes = $wpdb->get_results(
            "SELECT Id, RazonSocial, NumeroDocumento, EsCliente, EsProveedor 
            FROM bc_cliente 
            WHERE (EsCliente = 1 OR EsProveedor = 1) 
            AND Activo = 1 
            ORDER BY RazonSocial"
        );
    } else {
        $clientes = $wpdb->get_results($wpdb->prepare(
            "SELECT Id, RazonSocial, NumeroDocumento, EsCliente, EsProveedor 
            FROM bc_cliente 
            WHERE (EsCliente = 1 OR EsProveedor = 1) 
            AND IdAliado = %d
            AND Activo = 1 
            ORDER BY RazonSocial",
            (int)$usuario->IdAliado
        ));
    }
    // Genera el HTML del select
    ?>
   <input type="hidden" id="IdEntradaBitacora" name="idEntradaBitacora" />
   <div class="form-row">
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
   <div class="form-row">
       <label for="cliente_proveedor">Cliente/Proveedor</label>
       <select class="input" name="cliente_proveedor" id="cliente_proveedor" required>
           <option value="">Seleccione...</option>
           <?php foreach ($clientes as $cliente): 
               $tipo = [];
               if ($cliente->EsCliente) $tipo[] = 'Cliente';
               if ($cliente->EsProveedor) $tipo[] = 'Proveedor';
               $tipoTexto = implode('/', $tipo);
           ?>
               <option value="<?= esc_attr($cliente->Id) ?>" 
                       data-tipo="<?= esc_attr($tipoTexto) ?>"
                       data-numero-documento="<?= esc_attr($cliente->NumeroDocumento) ?>"
                       data-razon-social="<?= esc_attr($cliente->RazonSocial) ?>">
                   <?= esc_html($cliente->RazonSocial) ?> - <?= esc_html($cliente->NumeroDocumento) ?> (<?= esc_html($tipoTexto) ?>)
               </option>
           <?php endforeach; ?>
       </select>
       <input type="hidden" name="id_cliente" id="id_cliente">
   </div>
   <div class="form-row">
       <label for="FechaDocumento">Fecha Documento</label>
       <input type="date" id="FechaDocumento" name="FechaDocumento" required />
   </div>
   <div class="form-row">
       <label>Descripción</label>
       <textarea id="Descripcion" name="descripcion" rows="4" style="resize: vertical; width: 100%;" required></textarea>
   </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectClienteProveedor = document.getElementById('cliente_proveedor');
    const inputIdCliente = document.getElementById('id_cliente');
    
    if (selectClienteProveedor) {
        selectClienteProveedor.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            
            if (this.value) {
                // Actualizamos el campo oculto con el ID del cliente seleccionado
                inputIdCliente.value = this.value;
            } else {
                // Limpiamos el campo si no hay selección
                inputIdCliente.value = '';
            }
        });
    }
});
</script>