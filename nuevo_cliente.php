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
$tabla = 'bc_cliente';

// 2. Procesar envío
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  check_admin_referer('crear_proceso_action', 'crear_proceso_nonce');

  // Recolectar y sanitizar datos
  $campos = [
    'EsCliente',
    'IdTipoDocumento',
    'NumeroDocumento',
    'RazonSocial',
    'Direccion',
    'IdPais',
    'IdCiudad',
    'NumeroCelular',
    'CorreoElectronico',
    'ActividadEconomica',
    'IdRegimen',
    'ResponsableIva',
    'AplicaRetenciones',
    'Activo',
    'IdUser',
    'FechaCreacion'
  ];
  $data = [];
  echo "<script>console.log(" . json_encode($_POST) . ");</script>";
  foreach ($campos as $campo) {
    if (isset($_POST[$campo])) {
      $valor = $_POST[$campo];
      if (in_array($campo, ['ResponsableIva', 'EsCliente','AplicaRetenciones'])) {
       
        if($campo == 'EsCliente'){
          $data[$campo] = (intval($valor)) == 0 ? 0 : 1;
        }
        else{
          $data[$campo] = (intval($valor)) == 0 ? 1 : 0;
        }
        
        echo "<script>console.log(" . json_encode(intval($valor)) . ");</script>";
      } else {
        $data[$campo] = sanitize_text_field($valor);
      }
    }
  }

  if (!array_key_exists('EsCliente', $data)) {
    $data['EsCliente']     = 1;
  }
  if (!array_key_exists('ResponsableIva', $data)) {
    $data['ResponsableIva']     = 0;
  }
  if (!array_key_exists('AplicaRetenciones', $data)) {
    $data['AplicaRetenciones']     = 0;
  }


  // Auditoría

  $data['IdUser']     = get_current_user_id();
  $data['FechaCreacion'] = current_time('mysql');
  $data['Activo']     = 1;
  echo "<script>console.log(" . json_encode($data) . ");</script>";

  // Insertar
  $inserted = $wpdb->insert($tabla, $data);

  if ($inserted) {
    $new_id = $wpdb->insert_id;
    $message = '<div class="success">Cliente creado con ID: ' . $new_id . '</div>';
  } else {
    $message = '<div class="error">Error al crear el cliente.</div>';
  }
}
$tipoIdentificacion = $wpdb->get_results("SELECT * FROM bc_tipo_documento");
$regimenes = $wpdb->get_results("SELECT * FROM bc_regimen");
$paises = $wpdb->get_results("SELECT * FROM bc_pais");
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
    <form method="post" action="" class="form-grid" onsubmit="showLoader()">
      <?php wp_nonce_field('crear_proceso_action', 'crear_proceso_nonce'); ?>

      <!-- Checkbox para elegir Cliente o Importador -->
      <div class="form-group checkbox-group">
        <label for="es_importador">Marcar si es Importador</label>
        <input type="checkbox" id="EsCliente" name="EsCliente" />
      </div>

      <?php
      $labels = [
        'TipoDocumento' => 'Tipo de Documento',
        'NumeroDocumento' => 'Documento',
        'RazonSocial' => 'Razon Social',
        'Direccion' => 'Dirección',
        'Pais' => 'Pais',
        'Departamento' => 'Departamento',
        'Ciudad' => 'Ciudad',
        'NumeroCelular' => 'Telefono',
        'CorreoElectronico' => 'Correo',
        'ActividadEconomica' => 'Actividad Economica',
        'ResponsableIva' => 'Responsable Iva',
        'AplicaRetenciones' => 'Aplica Retenciones',
        'Regimen' => 'Regimen'
      ];
      foreach ($labels as $name => $label): ?>
        <div class="form-group">
          <label for="<?php echo $name; ?>"><?php echo $label; ?>:</label>

          <?php if ($name === 'TipoDocumento'): ?>
            <select id="IdTipoDocumento" name="IdTipoDocumento" required>
              <option value="">Seleccione...</option>
              <?php foreach ($tipoIdentificacion as $tipo): ?>
                <option value="<?= esc_attr($tipo->Id) ?>">
                  <?= esc_html($tipo->Descripcion) ?>
                </option>
              <?php endforeach; ?>
            </select>
          <?php elseif ($name === 'Regimen'): ?>
            <select id="IdRegimen" name="IdRegimen" required>
              <option value="">Seleccione...</option>
              <?php foreach ($regimenes as $regimen): ?>
                <option value="<?= esc_attr($regimen->Id) ?>">
                  <?= esc_html($regimen->Descripcion) ?>
                </option>
              <?php endforeach; ?>
            </select>
          <?php elseif ($name === 'Pais'): ?>
            <select id="IdPais" name="IdPais" required>
              <option value="">Seleccione...</option>
              <?php foreach ($paises as $pais): ?>
                <option value="<?= esc_attr($pais->Id) ?>">
                  <?= esc_html($pais->Descripcion) ?>
                </option>
              <?php endforeach; ?>
            </select>
          <?php elseif ($name === 'Departamento'): ?>
            <select id="IdDepartamento" name="IdDepartamento" required>
              <option value="">Seleccione...</option>
            </select>
          <?php elseif ($name === 'Ciudad'): ?>
            <select id="IdCiudad" name="IdCiudad" required>
              <option value="">Seleccione...</option>
            </select>
          <?php elseif ($name === 'ResponsableIva'): ?>
            <input type="checkbox" id="ResponsableIva" name="ResponsableIva" />
          
          <?php elseif ($name === 'AplicaRetenciones'): ?>
            <input type="checkbox" id="AplicaRetenciones" name="AplicaRetenciones" />

          <?php else: ?>
            <input type="text" id="<?php echo $name; ?>" name="<?php echo $name; ?>" required />
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <div class="form-buttons" style="display: flex; justify-content: center; gap: 20px; ">
        <a href="?view=clientes" class="btn" style="width: 150px; text-align: center;">Cerrar</a>
        <button type="submit" class="btn" style="width: 150px;">Crear</button>
      </div>

    </form>
  </div>
</body>
<div id="loader-overlay">
  <div class="spinner"></div>
</div>

</html>
<script>
  const ajaxUrl = '/wp-content/bitacoras/plugins/cliente/cliente-ajax.php'; // ajusta la ruta real

  document.getElementById('IdPais').addEventListener('change', function() {
    const idPais = this.value;
    showLoader();
    fetch(`${ajaxUrl}?action=get_departamentos&id_pais=${idPais}`)
      .then(res => res.json())
      .then(data => {
        const depSelect = document.getElementById('IdDepartamento');
        depSelect.innerHTML = '<option value="">Seleccione...</option>';
        data.forEach(dep => {
          const option = document.createElement('option');
          option.value = dep.Id;
          option.textContent = dep.Descripcion;
          depSelect.appendChild(option);
        });

        document.getElementById('IdCiudad').innerHTML = '<option value="">Seleccione...</option>';
      }).finally(hideLoader);
  });

  document.getElementById('IdDepartamento').addEventListener('change', function() {

    const idDep = this.value;
    showLoader();
    fetch(`${ajaxUrl}?action=get_ciudades&id_departamento=${idDep}`)
      .then(res => res.json())
      .then(data => {
        const ciudadSelect = document.getElementById('IdCiudad');
        ciudadSelect.innerHTML = '<option value="">Seleccione...</option>';
        data.forEach(ciudad => {
          const option = document.createElement('option');
          option.value = ciudad.Id;
          option.textContent = ciudad.Descripcion;
          ciudadSelect.appendChild(option);
        });
      }).finally(hideLoader);
  });

  function showLoader() {
    document.getElementById('loader-overlay').style.display = 'flex';
  }

  function hideLoader() {
    document.getElementById('loader-overlay').style.display = 'none';
  }
</script>