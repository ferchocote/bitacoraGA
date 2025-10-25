<?php
// Valida rol de usuario 
if ($usuario->rol_codigo != "RRHH" && $usuario->rol_codigo != "ADMIN") {
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
$tabla = 'bc_cliente';

// 2. Procesar envío
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  check_admin_referer('crear_proceso_action', 'crear_proceso_nonce');

  // Recolectar y sanitizar datos
  $campos = [
    'EsCliente',
    'EsProveedor',
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
    if (!isset($_POST[$campo])) {
      continue;
    }

    $valor = $_POST[$campo];
    if (in_array($campo, ['ResponsableIva', 'AplicaRetenciones'])) {
      $data[$campo] = (intval($valor)) == 0 ? 1 : 0;
      echo "<script>console.log(" . json_encode(intval($valor)) . ");</script>";
    } elseif (in_array($campo, ['EsCliente', 'EsProveedor'])) {
      // Se procesan m��s abajo para aplicar la regla de exclusividad.
      continue;
    } else {
      $data[$campo] = sanitize_text_field($valor);
    }
  }

  if (!array_key_exists('ResponsableIva', $data)) {
    $data['ResponsableIva']     = 0;
  }
  if (!array_key_exists('AplicaRetenciones', $data)) {
    $data['AplicaRetenciones']     = 0;
  }

  $esImportadorMarcado = !empty($_POST['EsCliente']);
  $esProveedorMarcado = !empty($_POST['EsProveedor']);
  if ($esImportadorMarcado && $esProveedorMarcado) {
    $esProveedorMarcado = false;
  }
  $data['EsProveedor'] = $esProveedorMarcado ? 1 : 0;
  $data['EsCliente'] = ($esImportadorMarcado || $esProveedorMarcado) ? 0 : 1;

  // Auditoría

  $data['IdUser']     = get_current_user_id();
  $data['FechaCreacion'] = current_time('mysql');
  $data['Activo']     = 1;
  
  // Insertar
  $inserted = $wpdb->insert($tabla, $data);

  if ($inserted) {
    $new_id = $wpdb->insert_id;
    $message = '<div class="success">Cliente creado con ID: ' . $new_id . '</div>';
    // Guardar log en bc_logs
    $log_data = [
        'Objeto'        => wp_json_encode($data),
        'Tabla'         => $tabla,
        'TipoDeCambio'    => 'Crear',
        'IdUser'        => get_current_user_id(),
        'FechaCreacion' => current_time('mysql'),
    ];
    $wpdb->insert('bc_logs', $log_data);

    
    // === NUEVO: si es Cliente (checkbox NO marcado) creamos usuario y relación ===
    $esCliente = isset($data['EsCliente']) ? (int)$data['EsCliente'] : 1; // por defecto 1 en tu código
    if ($esCliente === 1) {
        // 1) Preparar login/email/clave
        $raw_login = !empty($data['NumeroDocumento']) ? $data['NumeroDocumento'] : $data['RazonSocial'];
        $user_login = sanitize_user($raw_login, true);
        if ($user_login === '') {
            $user_login = 'cli_' . wp_generate_password(6, false, false);
        }

        // Evitar colisiones en username
        $base_login = $user_login; $i = 1;
        while (username_exists($user_login)) {
          $user_login = $base_login . $i++;
        }

        $user_email = sanitize_email($data['CorreoElectronico']);
        // fallback si email vacío o repetido
        if (empty($user_email) || email_exists($user_email)) {
            $user_email = $user_login . '@example.invalid';
        }

        $password = wp_generate_password(12, true, true);

        $user_id = wp_insert_user([
        'user_login'   => $user_login,
        'user_pass'    => $password,
        'user_email'   => $user_email,
        'display_name' => !empty($data['RazonSocial']) ? $data['RazonSocial'] : $user_login,
        'role'         => 'subscriber', // ajústalo si usas otro rol para clientes
      ]);

      if (!is_wp_error($user_id)) {
        

        // 6) correo con credenciales
        $login_url = 'https://galogistic.com/iniciar-sesion/';

        // Habilitar HTML SOLO para este envío
        $set_html = function () { return 'text/html; charset=UTF-8'; };
        add_filter('wp_mail_content_type', $set_html);

        $headers = [
                'From: GA LOGISTIC <subgerencia@galogistic.com>',
                'Reply-To: Soporte <solucionestegnologicasga@gmail.com>',
            ];

        $body = sprintf(
              '<p>Hola,</p>
              <p>Se ha creado tu acceso al portal.</p>
              <p><strong>Usuario:</strong> %s<br>
                  <strong>Contraseña temporal:</strong> %s</p>
              <p>Puedes iniciar sesión aquí: <a href="%s">%s</a></p>
              <p>Si necesitas cambiar tu contraseña o tienes cualquier duda, por favor comunícate con el administrador respondiendo a este correo.</p>',
              esc_html($user_login),
              esc_html($password),
              esc_url($login_url),
              esc_html($login_url)
            );

        $sent = wp_mail($user_email, 'Acceso a la plataforma', $body, $headers);

        remove_filter('wp_mail_content_type', $set_html);

        if (!$sent) {
          error_log('No se pudo enviar el correo de credenciales a ' . $user_email);
        }

        // 5) relación en bc_cliente_empresa
        $wpdb->insert('bc_cliente_empresa', [
          'IdUser'    => $user_id,
          'IdCliente' => $new_id,
        ]);

        error_log( $wpdb->last_query );
        error_log( $wpdb->last_error );

      } else {
        error_log('Error al crear usuario WP: ' . $user_id->get_error_message());
      }
    }
    // === FIN NUEVO ===

    $message = '<div class="success">Cliente creado con ID: ' . $new_id . '</div>';
  } else {
    $message = '<div class="error">Error al crear el cliente.</div>';
  }
}
$tipoIdentificacion = $wpdb->get_results("SELECT * FROM bc_tipo_documento");
$regimenes = $wpdb->get_results("SELECT * FROM bc_regimen");
$paises = $wpdb->get_results("SELECT * FROM bc_pais");
?>
<script src="/wp-content/bitacoras/assets/js/common-loader.js"></script>
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

      <!-- Checkbox para elegir si es Importador o Proveedor -->
      <div class="form-group checkbox-group">
        <label class="checkbox-option" for="EsCliente">
          <input type="checkbox" id="EsCliente" name="EsCliente" />
          Marcar si es Importador
        </label>
        <label class="checkbox-option" for="EsProveedor">
          <input type="checkbox" id="EsProveedor" name="EsProveedor" />
          Marcar si es Proveedor
        </label>
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

      <div class="form-buttons">
        <a href="?view=clientes" class="btn close">Cerrar</a>
        <button type="submit" class="btn">Crear</button>
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
   document.addEventListener('DOMContentLoaded', function() {
        hideLoader();
        // Selecciona todos los enlaces dentro del sidebar (tu menú principal)
        const sidebarLinks = document.querySelectorAll('.form-buttons a');

        // Función auxiliar para añadir el evento de clic a una colección de enlaces
        function addLoaderToLinks(links) {
            links.forEach(function(link) {
                // Añade un listener de clic a cada enlace
                link.addEventListener('click', function() {
                    // Llama a la función showLoader() que está en common-loader.js
                    hideLoader();
                    showLoader();
                });
            });
        }

        // Aplica la función a los enlaces del sidebar
        addLoaderToLinks(sidebarLinks);

        const chkImportador = document.getElementById('EsCliente');
        const chkProveedor = document.getElementById('EsProveedor');
        if (chkImportador && chkProveedor) {
            chkImportador.addEventListener('change', function() {
                if (chkImportador.checked) {
                    chkProveedor.checked = false;
                }
            });
            chkProveedor.addEventListener('change', function() {
                if (chkProveedor.checked) {
                    chkImportador.checked = false;
                }
            });
        }
    });

</script>
