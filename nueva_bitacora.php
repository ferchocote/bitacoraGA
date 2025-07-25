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
$tabla = $wpdb->prefix . 'procesos';

// 2. Procesar envío
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_admin_referer('crear_proceso_action', 'crear_proceso_nonce');

    // Recolectar y sanitizar datos
    $campos = [
      'DO','Encargado','IdImportador','TipoProceso','DOAgencia','AgenteCarga',
      'ETA','DiasLibres','DigitacionRevision','Aduana','Producto','NumeroBL',
      'Contenedor','Manifiesto','Pies','Bulto','PesoBruto','Bandera','IdEstadoProceso'
    ];
    $data = [];
    foreach ($campos as $campo) {
        if (isset($_POST[$campo])) {
            $valor = $_POST[$campo];
            if (in_array($campo, ['IdImportador','IdEstadoProceso'])) {
                $data[$campo] = intval($valor);
            } elseif ($campo === 'ETA') {
                $data[$campo] = date('Y-m-d H:i:s', strtotime($valor));
            } else {
                $data[$campo] = sanitize_text_field($valor);
            }
        }
    }
    // Auditoría
    $data['CreatedBy']     = get_current_user_id();
    $data['FechaCreacion'] = current_time('mysql');

    // Insertar
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
  <title>Crear Proceso</title>
  <link rel="stylesheet" href="styles/style.css">
</head>
<body>
  <div class="form-container">
    <h1>Crear Nuevo Proceso (DO)</h1>
    <?php echo $message; ?>
    <form method="post" action="" class="form-grid">
      <?php wp_nonce_field('crear_proceso_action','crear_proceso_nonce'); ?>

      <?php
      $labels = [
        'DO'=>'DO','Encargado'=>'Encargado','IdEmpresa'=>'Cliente','IdImportador'=>'Importador','TipoProceso'=>'Tipo Proceso',
        'DOAgencia'=>'DO Agencia','AgenteCarga'=>'Agente Carga','ETA'=>'ETA','DiasLibres'=>'Días Libres',
        'DigitacionRevision'=>'Digitación/Revisión','Aduana'=>'Aduana','Producto'=>'Producto','NumeroBL'=>'Número BL',
        'Contenedor'=>'Contenedor','Manifiesto'=>'Manifiesto','Pies'=>'Pies','Bulto'=>'Bulto',
        'PesoBruto'=>'Peso Bruto','Bandera'=>'Bandera'
      ];
      foreach ($labels as $name => $label): ?>
        <div class="form-group">
          <label for="<?php echo $name; ?>"><?php echo $label; ?>:</label>
          <?php if (in_array($name, ['IdImportador','IdEstadoProceso'])): ?>
            <input type="number" id="<?php echo $name; ?>" name="<?php echo $name; ?>" required />
          <?php elseif ($name === 'ETA'): ?>
            <input type="datetime-local" id="<?php echo $name; ?>" name="<?php echo $name; ?>" required />
          <?php else: ?>
            <input type="text" id="<?php echo $name; ?>" name="<?php echo $name; ?>" required />
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <div class="form-group">
        <div></div>
        <button type="submit">Crear Proceso</button>
      </div>
    </form>
  </div>
</body>
</html>