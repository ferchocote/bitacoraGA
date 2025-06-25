<?php
define('WP_USE_THEMES', false);
require_once('../../wp-load.php');

global $wpdb;
$tabla = $wpdb->prefix . 'users';
$bitacoras = $wpdb->get_results("SELECT * FROM wp_users ");
$proceso = (object)[
  'DO'                => 'I25-001',
  'Importador'        => 'davidalfredobr',
  'EstadoProceso'     => 'Creado',
  'FechaCreacion'     => '18/06/2025',
  'TipoProceso'       => 'Importación',
  'DOAgencia'         => 'AG123',
  'AgenteCarga'       => 'ACME Logistics',
  'ETA'               => '20/06/2025',
  'DiasLibres'        => '21',
  'DigitacionRevision'=> 'AAA',
  'Aduana'            => 'SI',
  'Producto'          => 'Textiles',
  'NumeroBL'          => 'BL000123',
  'Contenedor'        => 'C12345',
  'Manifiesto'        => 'MNF-456',
  'Pies'              => '40',
  'Bulto'             => '10',
  'PesoBruto'         => '2000kg',
  'Bandera'           => 'Panamá'
];
?>

<!DOCTYPE html>
<h1>Entradas Bitácora</h1>

    <!-- Sección de resumen: una sola tarjeta con múltiples datos -->
    <input type="checkbox" id="popup-toggle-edit" hidden>
      <div class="summary-card single">
        <label for="popup-toggle-edit" class="edit-icon" title="Editar Proceso">⚙️</label>
        <h2>Información del Proceso</h2>
        <div class="summary-grid">
          <div><strong>DO:</strong> I25-001</div>
          <div><strong>Importador:</strong> davidalfredobr</div>
          <div><strong>Estado:</strong> <span class="status-label status-creado">Creado</span></div>
          <div><strong>Creado el:</strong> 18/06/2025</div>
          <div><strong>Tipo Proceso:</strong> davidalfredobr</div>
          <div><strong>DO Agencia:</strong> davidalfredobr</div>
          <div><strong>Agente Carga:</strong> davidalfredobr</div>
          <div><strong>ETA:</strong> 18/06/2025</div>
          <div><strong>Días Libres:</strong> 21</div>
          <div><strong>Digitación/Revision:</strong> AAA</div>
          <!-- Agrega más campos según necesidad -->
        </div>
      </div>
      
      
<input type="checkbox" id="popup-toggle-add" hidden>
<div class="toolbar" style="margin-bottom: 20px; display: flex; gap: 10px;">
    <label for="popup-toggle-add" class="btn">➕ Nueva Entrada</label>
</div>

<?php if (empty($bitacoras)): ?>
    <p>No hay entradas en la bitácora.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Descripción</th>
                <th>Nombre de Usuario</th>
                <th>Correo</th>
                <th>Fecha de Creación</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bitacoras as $b): ?>
                <tr>
                    <td>Trasnporte</td>
                    <td><?= esc_html($b->user_login) ?></td>
                    <td><?= esc_html($b->user_nicename) ?></td>
                    <td><?= esc_html($b->user_email) ?></td>
                    <td>18/06/2025</td>
                    <td>
                        <a class="btn">Ver detalle</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- Popup de edición/formulario completo -->
      <div class="overlay-edit">
      <div class="popup-edit">
        <h3>Editar Proceso</h3>
        <form method="post" action="?view=editar_proceso&id=<?= esc_attr($proceso->DO); ?>">
          <?php wp_nonce_field('editar_proceso_action','editar_proceso_nonce'); ?>
          <div class="popup-grid">
              <?php foreach ([
                'DO'=>'DO','IdImportador'=>'Importador','EstadoProceso'=>'Estado','FechaCreacion'=>'Fecha Creación',
                'TipoProceso'=>'Tipo Proceso','DOAgencia'=>'DO Agencia','AgenteCarga'=>'Agente Carga','ETA'=>'ETA',
                'DiasLibres'=>'Días Libres','DigitacionRevision'=>'Digitación/Revisión','Aduana'=>'Aduana',
                'Producto'=>'Producto','NumeroBL'=>'Número BL','Contenedor'=>'Contenedor','Manifiesto'=>'Manifiesto',
                'Pies'=>'Pies','Bulto'=>'Bulto','PesoBruto'=>'Peso Bruto','Bandera'=>'Bandera'
              ] as $field => $label): ?>
                <div class="popup-field">
                  <label for="<?= $field; ?>"><?= $label; ?>:</label>
                  <input type="text" id="<?= $field; ?>" name="<?= $field; ?>" value="<?= esc_attr($proceso->$field); ?>" />
                </div>
              <?php endforeach; ?>
            </div>
          <div class="popup-actions">
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
<?php endif; ?>