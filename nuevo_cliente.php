<?php
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
  <title>Crear</title>
  <link rel="stylesheet" href="styles/style.css">
</head>
<body>
  <div class="form-container">
    <h1>Crear Nuevo Cliente o Importador</h1>
    <?php echo $message; ?>
    <form method="post" action="" class="form-grid">
      <?php wp_nonce_field('crear_proceso_action','crear_proceso_nonce'); ?>

      <!-- Checkbox para elegir Cliente o Importador -->
      <div class="form-group checkbox-group">
        <label for="es_importador">Marcar si es Importador</label>
        <input type="checkbox" id="es_importador" name="es_importador" />       
      </div>

      <?php
      $labels = [
        'TipoDocumento'=>'Tipo de Documento','Documento'=>'Documento','RazonSocial'=>'Razon Social','Direccion'=>'Dirección','Pais'=>'Pais',
        'Departamento'=>'Departamento','Ciudad'=>'Ciudad','Telefono'=>'Telefono','Correo'=>'Correo',
        'ActividadEconomica'=>'Actividad Economica','ResponsableIva'=>'Responsable Iva','Regimen'=>'Regimen'
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
        <button type="submit">Crear</button>
      </div>
    </form>
  </div>
</body>
</html>