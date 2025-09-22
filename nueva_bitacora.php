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

$aduanas = $wpdb->get_results("SELECT Id, Descripcion FROM bc_catalogo WHERE Tipo='Aduana' AND Activo=1 ORDER BY Descripcion");
$pies    = $wpdb->get_results("SELECT Id, Descripcion FROM bc_catalogo WHERE Tipo='Pies' AND Activo=1 ORDER BY Descripcion");
$puertos = $wpdb->get_results("SELECT Id, Descripcion FROM bc_catalogo WHERE Tipo='Puerto' AND Activo=1 ORDER BY Descripcion");
$tipos   = $wpdb->get_results("SELECT Id, Descripcion FROM bc_catalogo WHERE Tipo='TipoProceso' AND Activo=1 ORDER BY Descripcion");
$digitaciones = $wpdb->get_results("SELECT Id, Descripcion FROM bc_catalogo WHERE Tipo='DigitacionRevision' AND Activo=1 ORDER BY Descripcion");

// ID del estado "Creado"
$estado_creado_id = $wpdb->get_var("SELECT Id FROM {$tabla_estados} WHERE Codigo='CREA' AND Activo=1");

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_admin_referer('crear_proceso_action', 'crear_proceso_nonce');

    // Normaliza y valida DO primero
    $do_ingresado = sanitize_text_field($_POST['DO']);
    $do_normalizado = trim($do_ingresado);

    // Valida existencia (case-insensitive y sin espacios en extremos)
    $ya_existe = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) 
             FROM {$tabla} 
             WHERE UPPER(TRIM(DO)) = UPPER(TRIM(%s))",
            $do_normalizado
        )
    );

    if ($ya_existe > 0) {
        $message = '<div class="error">El DO <strong>' . esc_html($do_ingresado) . '</strong> ya existe en el sistema.</div>';
    } else {

    // Campos que vienen del formulario
    $data = [];
    $data['DO']               = sanitize_text_field($_POST['DO']);
    $data['Encargado']        = $current_user->user_login;
    $data['IdCliente']        = intval($_POST['IdEmpresa']);
    $data['IdImportador']     = intval($_POST['IdImportador']);
    $data['DOAgencia']        = sanitize_text_field($_POST['DOAgencia']);
    $data['AgenteCarga']      = sanitize_text_field($_POST['AgenteCarga']);
    $data['ETA']              = date('Y-m-d H:i:s', strtotime($_POST['ETA']));
    $data['DiasLibres']       = sanitize_text_field($_POST['DiasLibres']);
    $data['Producto']         = sanitize_text_field($_POST['Producto']);
    $data['NumeroBL']         = sanitize_text_field($_POST['NumeroBL']);
    $data['Contenedor']       = sanitize_text_field($_POST['Contenedor']);
    $data['Bulto']            = sanitize_text_field($_POST['Bulto']);
    $data['PesoBruto']        = sanitize_text_field($_POST['PesoBruto']);
    $data['Bandera']          = sanitize_text_field($_POST['Bandera']);
    $data['IdTipoProceso']        = intval($_POST['IdTipoProceso']);
    $data['IdDigitacionRevision'] = intval($_POST['IdDigitacionRevision']);
    $data['IdAduana']             = intval($_POST['IdAduana']);
    $data['IdPies']               = intval($_POST['IdPies']);
    $data['IdPuerto']             = intval($_POST['IdPuerto']);

    // Estado y auditoría
    $data['IdEstadoProceso'] = intval($estado_creado_id);
    $data['IdUserCreation']  = get_current_user_id();
    $data['FechaCreacion']   = current_time('mysql');
    $data['Activo']          = 1;

    // Insertar registro
    $inserted = $wpdb->insert($tabla, $data);
    if ($inserted) {
      $new_id = $wpdb->insert_id;
      // Guardar log en bc_logs
      $log_data = [
        'Objeto'        => wp_json_encode($data),
        'Tabla'         => $tabla,
        'TipoDeCambio'    => 'Crear',
        'IdUser'        => get_current_user_id(),
        'FechaCreacion' => current_time('mysql'),
      ];
      $wpdb->insert('bc_logs', $log_data);
      $message = '<div class="success">Proceso creado con ID: ' . $new_id . '</div>';
    } else {
        $message = '<div class="error">Error al crear el proceso.</div>';
    }
  }
}

// Helpers para “valores antiguos” (sticky form)
function old($key, $default = '') {
  return isset($_POST[$key]) ? esc_attr(wp_unslash($_POST[$key])) : $default;
}
// Para <select> (comparación segura)
function old_is($key, $value) {
  if (!isset($_POST[$key])) return false;
  // compara como string para evitar falsos negativos
  return (string) $_POST[$key] === (string) $value;
}
// Para inputs datetime-local (el navegador espera YYYY-MM-DDTHH:MM)
function old_dt($key, $default = '') {
  if (!isset($_POST[$key]) || $_POST[$key] === '') return esc_attr($default);
  // Asumimos que ya viene en formato correcto porque lo reinyectamos tal cual
  return esc_attr(wp_unslash($_POST[$key]));
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
            <option value="<?= esc_attr($c->Id) ?>" <?= old_is('IdEmpresa', $c->Id) ? 'selected' : '' ?>>
              <?= esc_html($c->RazonSocial) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="IdImportador">Importador:</label>
        <select id="IdImportador" name="IdImportador" required>
          <option value="">Selecciona un importador</option>
          <?php foreach($importadores as $imp): ?>
            <option value="<?= esc_attr($imp->Id) ?>" <?= old_is('IdImportador', $imp->Id) ? 'selected' : '' ?>>
              <?= esc_html($imp->RazonSocial) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="DO">DO:</label>
        <input type="text" id="DO" name="DO" required value="<?= old('DO') ?>">
      </div>

      <div class="form-group">
        <label for="IdTipoProceso">Tipo de Proceso:</label>
        <select id="IdTipoProceso" name="IdTipoProceso" required>
          <option value="">Selecciona tipo</option>
          <?php foreach($tipos as $t): ?>
            <option value="<?= esc_attr($t->Id) ?>" <?= old_is('IdTipoProceso', $t->Id) ? 'selected' : '' ?>>
              <?= esc_html($t->Descripcion) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="DOAgencia">DO Agencia:</label>
        <input type="text" id="DOAgencia" name="DOAgencia" value="<?= old('DOAgencia') ?>">
      </div>

      <div class="form-group">
        <label for="AgenteCarga">Agente de carga/Naviera:</label>
        <input type="text" id="AgenteCarga" name="AgenteCarga" value="<?= old('AgenteCarga') ?>">
      </div>

      <div class="form-group">
        <label for="ETA">ETA:</label>
        <input type="datetime-local" id="ETA" name="ETA" value="<?= old_dt('ETA') ?>">
      </div>

      <div class="form-group">
        <label for="DiasLibres">Días Libres:</label>
        <input type="text" id="DiasLibres" name="DiasLibres" value="<?= old('DiasLibres') ?>">
      </div>

      <div class="form-group">
        <label for="IdDigitacionRevision">Digitación/Revisión:</label>
        <select id="IdDigitacionRevision" name="IdDigitacionRevision" required>
          <option value="">Selecciona opción</option>
          <?php foreach($digitaciones as $d): ?>
            <option value="<?= esc_attr($d->Id) ?>" <?= old_is('IdDigitacionRevision', $d->Id) ? 'selected' : '' ?>>
              <?= esc_html($d->Descripcion) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="IdAduana">Aduana:</label>
        <select id="IdAduana" name="IdAduana" required>
          <option value="">Selecciona aduana</option>
          <?php foreach($aduanas as $a): ?>
            <option value="<?= esc_attr($a->Id) ?>" <?= old_is('IdAduana', $a->Id) ? 'selected' : '' ?>>
              <?= esc_html($a->Descripcion) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="Producto">Producto:</label>
        <input type="text" id="Producto" name="Producto" value="<?= old('Producto') ?>">
      </div>

      <div class="form-group">
        <label for="NumeroBL">Número BL:</label>
        <input type="text" id="NumeroBL" name="NumeroBL" value="<?= old('NumeroBL') ?>">
      </div>

      <div class="form-group">
        <label for="Contenedor">Contenedor:</label>
        <input type="text" id="Contenedor" name="Contenedor" value="<?= old('Contenedor') ?>">
      </div>

      <div class="form-group">
        <label for="IdPies">Pies:</label>
        <select id="IdPies" name="IdPies" required>
          <option value="">Selecciona pies</option>
          <?php foreach($pies as $p): ?>
            <option value="<?= esc_attr($p->Id) ?>" <?= old_is('IdPies', $p->Id) ? 'selected' : '' ?>>
              <?= esc_html($p->Descripcion) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="IdPuerto">Puerto:</label>
        <select id="IdPuerto" name="IdPuerto" required>
          <option value="">Selecciona puerto</option>
          <?php foreach($puertos as $pt): ?>
            <option value="<?= esc_attr($pt->Id) ?>" <?= old_is('IdPuerto', $pt->Id) ? 'selected' : '' ?>>
              <?= esc_html($pt->Descripcion) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="Bulto">Bulto:</label>
        <input type="text" id="Bulto" name="Bulto" value="<?= old('Bulto') ?>">
      </div>

      <div class="form-group">
        <label for="PesoBruto">Peso Bruto:</label>
        <input type="text" id="PesoBruto" name="PesoBruto" value="<?= old('PesoBruto') ?>">
      </div>

      <div class="form-group">
        <label for="Bandera">Bandera:</label>
        <input type="text" id="Bandera" name="Bandera" value="<?= old('Bandera') ?>">
      </div>

      <div class="form-group last">
        <a href="?view=bitacoras" class="btn close">Cerrar</a>
        <button type="submit" class="btn">Crear Proceso</button>
      </div>
    </form>
  </div>
</body>
</html>