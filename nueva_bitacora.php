<?php
// Valida rol de usuario 
if ($usuario->rol_codigo != "IMPOR" && $usuario->rol_codigo != "ADMIN") {
    echo "No tienes permiso para acceder a esta vista.";
    exit;
}

// 1. Cargar WP y verificar sesión
define('WP_USE_THEMES', false);
require_once __DIR__ . '/../../wp-load.php';

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url($_SERVER['REQUEST_URI']));
    exit;
}
$current_user = wp_get_current_user();

global $wpdb;
$tabla = 'bc_' . 'proceso';
$tabla_estados  = 'bc_' . 'estado_proceso';
$tabla_clientes = 'bc_' . 'cliente';

// Obtener listas para selects
$clientes = $wpdb->get_results("SELECT Id, RazonSocial FROM {$tabla_clientes} WHERE Activo=1 AND EsCliente=1 ORDER BY RazonSocial");
$importadores = $wpdb->get_results("SELECT Id, RazonSocial FROM {$tabla_clientes} WHERE Activo=1 AND EsCliente=0 ORDER BY RazonSocial");

// ID del estado "Creado"
$estado_creado_id = $wpdb->get_var("SELECT Id FROM {$tabla_estados} WHERE Codigo='CREA' AND Activo=1");

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_admin_referer('crear_proceso_action', 'crear_proceso_nonce');

    // Campos que vienen del formulario
    $data = [];
    $data['DO']               = sanitize_text_field($_POST['DO']);
    $data['Encargado']        = $current_user->user_login;
    $data['IdCliente']        = intval($_POST['IdEmpresa']);
    $data['IdImportador']     = intval($_POST['IdImportador']);
    $data['TipoProceso']      = sanitize_text_field($_POST['TipoProceso']);
    $data['DOAgencia']        = sanitize_text_field($_POST['DOAgencia']);
    $data['AgenteCarga']      = sanitize_text_field($_POST['AgenteCarga']);
    $data['ETA']              = date('Y-m-d H:i:s', strtotime($_POST['ETA']));
    $data['DiasLibres']       = sanitize_text_field($_POST['DiasLibres']);
    $data['DigitacionRevision']= sanitize_text_field($_POST['DigitacionRevision']);
    $data['Aduana']           = sanitize_text_field($_POST['Aduana']);
    $data['Producto']         = sanitize_text_field($_POST['Producto']);
    $data['NumeroBL']         = sanitize_text_field($_POST['NumeroBL']);
    $data['Contenedor']       = sanitize_text_field($_POST['Contenedor']);
    $data['Manifiesto']       = sanitize_text_field($_POST['Manifiesto']);
    $data['Pies']             = sanitize_text_field($_POST['Pies']);
    $data['Bulto']            = sanitize_text_field($_POST['Bulto']);
    $data['PesoBruto']        = sanitize_text_field($_POST['PesoBruto']);
    $data['Bandera']          = sanitize_text_field($_POST['Bandera']);

    // Estado y auditoría
    $data['IdEstadoProceso'] = intval($estado_creado_id);
    $data['IdUserCreation']  = get_current_user_id();
    $data['FechaCreacion']   = current_time('mysql');
    $data['Activo']          = 1;

    // Insertar registro
    $inserted = $wpdb->insert($tabla, $data);
    if ($inserted) {
        $new_id = $wpdb->insert_id;
        $message = '<div class="success">Proceso creado con ID: ' . $new_id . '</div>';
    } else {
        $message = '<div class="error">Error al crear el proceso.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Crear Nuevo Proceso (DO)</title>
  <link rel="stylesheet" href="styles/style.css">
</head>
<body>
  <div class="form-container">
    <h1>Crear Nuevo Proceso (DO)</h1>
    <?= $message ?>
    <form method="post" class="form-grid">
      <?php wp_nonce_field('crear_proceso_action','crear_proceso_nonce'); ?>

      <div class="form-group">
        <label for="IdEmpresa">Cliente:</label>
        <select id="IdEmpresa" name="IdEmpresa" required>
          <option value="">Selecciona un cliente</option>
          <?php foreach($clientes as $c): ?>
            <option value="<?= esc_attr($c->Id) ?>"><?= esc_html($c->RazonSocial) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="IdImportador">Importador:</label>
        <select id="IdImportador" name="IdImportador" required>
          <option value="">Selecciona un importador</option>
          <?php foreach($importadores as $imp): ?>
            <option value="<?= esc_attr($imp->Id) ?>"><?= esc_html($imp->RazonSocial) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="DO">DO:</label>
        <input type="text" id="DO" name="DO" required>
      </div>

      <div class="form-group">
        <label for="TipoProceso">Tipo de Proceso:</label>
        <input type="text" id="TipoProceso" name="TipoProceso" required>
      </div>

      <div class="form-group">
        <label for="DOAgencia">DO Agencia:</label>
        <input type="text" id="DOAgencia" name="DOAgencia">
      </div>

      <div class="form-group">
        <label for="AgenteCarga">Agente de Carga:</label>
        <input type="text" id="AgenteCarga" name="AgenteCarga">
      </div>

      <div class="form-group">
        <label for="ETA">ETA:</label>
        <input type="datetime-local" id="ETA" name="ETA">
      </div>

      <!-- Resto de campos -->
      <?php foreach([
        'DiasLibres'=>'Días Libres','DigitacionRevision'=>'Digitación/Revisión','Aduana'=>'Aduana',
        'Producto'=>'Producto','NumeroBL'=>'Número BL','Contenedor'=>'Contenedor','Manifiesto'=>'Manifiesto',
        'Pies'=>'Pies','Bulto'=>'Bulto','PesoBruto'=>'Peso Bruto','Bandera'=>'Bandera'
      ] as $field => $label): ?>
        <div class="form-group">
          <label for="<?= $field ?>"><?= $label ?>:</label>
          <input type="text" id="<?= $field ?>" name="<?= $field ?>">
        </div>
      <?php endforeach; ?>

      <div class="form-group last">
        <button type="submit" class="btn">Crear Proceso</button>
      </div>
    </form>
  </div>
</body>
</html>