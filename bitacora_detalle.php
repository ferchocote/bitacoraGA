<?php
ob_start();

// Valida rol de usuario 
if ($usuario->rol_codigo == "RRHH") {
    echo "No tienes permiso para acceder a esta vista.";
    exit;
}

define('WP_USE_THEMES', false);
require_once('../../wp-load.php');

if (!is_user_logged_in()) {
  wp_redirect(wp_login_url($_SERVER['REQUEST_URI']));
  exit;
}

global $wpdb;

add_action('phpmailer_init', function($phpmailer) {
    // Forzar SMTP
    $phpmailer->isSMTP();
    $phpmailer->Host       = 'smtp.hostinger.com';   // servidor SMTP de tu hosting
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Port       = 465;                    // 465 (SSL) o 587 (TLS)
    $phpmailer->SMTPSecure = 'ssl';                  // o 'tls'
    
    // Credenciales de la cuenta en tu dominio
    $phpmailer->Username   = 'no-reply@clscolombia.com';
    $phpmailer->Password   = 'Soluciones25*';
    
    // Dirección del remitente
    $phpmailer->setFrom('no-reply@clscolombia.com', 'CLS-COLOMBIA');
});

// Tablas
$tabla = 'bc_' . 'proceso';
$tabla_clientes = 'bc_' . 'cliente';
$tabla_estados = 'bc_' . 'estado_proceso';
$tabla_detalle = 'bc_' . 'detalle_proceso';
$tabla_tipo_entrada = 'bc_' . 'tipo_entrada';

// 1) Procesar formulario de edición antes de cualquier salida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['view']) && $_GET['view'] === 'bitacora_detalle') {
  check_admin_referer('editar_proceso_action', 'editar_proceso_nonce');
  $id = intval($_GET['id']);
  // Actualizar bc_proceso
  $tabla_proceso = $tabla;
  
  $sql = $wpdb->prepare(
    "SELECT p.* FROM bc_proceso p WHERE p.Id = %d", $id
  );
    
  $proceso = $wpdb->get_row($sql);
  
  $sqlDetalle = $wpdb->prepare(
    "SELECT d.* FROM bc_detalle_proceso d WHERE d.Id = %d", $id
  );
    
  $detalle = $wpdb->get_row($sqlDetalle);
  if ($detalle) {
    $detalle->PagoNaviera = $detalle->PagoNaviera ? date('Y-m-d H:i:s', strtotime($_POST['PagoNaviera'])) : null;
    $detalle->Liberacion = $detalle->Liberacion ? date('Y-m-d H:i:s', strtotime($_POST['Liberacion'])) : null;
    $detalle->Aceptacion = $detalle->Aceptacion ? date('Y-m-d H:i:s', strtotime($_POST['Aceptacion'])) : null;
    $detalle->Selectividad = $detalle->Selectividad ? date('Y-m-d H:i:s', strtotime($_POST['Selectividad'])) : null;
    $detalle->Levante = $detalle->Levante ? date('Y-m-d H:i:s', strtotime($_POST['Levante'])) : null;
    $detalle->EntregaTransporte = $detalle->EntregaTransporte ? date('Y-m-d H:i:s', strtotime($_POST['EntregaTransporte'])) : null;
    $detalle->DevolucionUnidad = $detalle->DevolucionUnidad ? date('Y-m-d H:i:s', strtotime($_POST['DevolucionUnidad'])) : null;
    $detalle->Pago = $detalle->Pago ? date('Y-m-d H:i:s', strtotime($_POST['Pago'])) : null;
    $detalle->Deposito = sanitize_text_field($_POST['Deposito']);
    $detalle->Manifiesto = sanitize_text_field($_POST['Manifiesto']);
    $detalle->Observaciones = sanitize_text_field($_POST['Observaciones']);
}
  
  $data_p = [
    'DOAgencia'           => sanitize_text_field($_POST['DOAgencia']),
    'AgenteCarga'         => sanitize_text_field($_POST['AgenteCarga']),
    'ETA'                 => date('Y-m-d H:i:s', strtotime($_POST['ETA'])),
    'DiasLibres'          => intval($_POST['DiasLibres']),
    'Producto'            => sanitize_text_field($_POST['Producto']),
    'NumeroBL'            => sanitize_text_field($_POST['NumeroBL']),
    'Contenedor'          => sanitize_text_field($_POST['Contenedor']),
    'Bulto'               => sanitize_text_field($_POST['Bulto']),
    'PesoBruto'           => sanitize_text_field($_POST['PesoBruto']),
    'Bandera'             => sanitize_text_field($_POST['Bandera']),
    'IdEstadoProceso'     => intval($_POST['IdEstadoProceso']),
    'IdCliente'           => intval($_POST['IdCliente']),
    'IdImportador'        => intval($_POST['IdImportador']),
  ];
  $data_p['IdTipoProceso']        = intval($_POST['IdTipoProceso']);
  $data_p['IdDigitacionRevision'] = intval($_POST['IdDigitacionRevision']);
  $data_p['IdAduana']             = intval($_POST['IdAduana']);
  $data_p['IdPies']               = intval($_POST['IdPies']);
  $data_p['IdPuerto']             = intval($_POST['IdPuerto']);


  $wpdb->update($tabla_proceso, $data_p, ['Id' => $id]);

  // Insertar o actualizar detalle
  $detalle_id = !empty($_POST['detalle_id']) ? intval($_POST['detalle_id']) : 0;
  $data_d = [
    'PagoNaviera'         => !empty($_POST['PagoNaviera']) ? date('Y-m-d H:i:s', strtotime($_POST['PagoNaviera'])) : null,
    'Liberacion'          => !empty($_POST['Liberacion']) ? date('Y-m-d H:i:s', strtotime($_POST['Liberacion'])) : null,
    'Aceptacion'          => !empty($_POST['Aceptacion']) ? date('Y-m-d H:i:s', strtotime($_POST['Aceptacion'])) : null,
    'Selectividad'        => !empty($_POST['Selectividad']) ? date('Y-m-d H:i:s', strtotime($_POST['Selectividad'])) : null,
    'Levante'             => !empty($_POST['Levante']) ? date('Y-m-d H:i:s', strtotime($_POST['Levante'])) : null,
    'EntregaTransporte'   => !empty($_POST['EntregaTransporte']) ? date('Y-m-d H:i:s', strtotime($_POST['EntregaTransporte'])) : null,
    'DevolucionUnidad'    => !empty($_POST['DevolucionUnidad']) ? date('Y-m-d H:i:s', strtotime($_POST['DevolucionUnidad'])) : null,
    'Pago'                => !empty($_POST['Pago']) ? date('Y-m-d H:i:s', strtotime($_POST['Pago'])) : null,
    'Deposito'            => sanitize_text_field($_POST['Deposito']),
    'Manifiesto'          => sanitize_text_field($_POST['Manifiesto']),
    'Observaciones'       => sanitize_text_field($_POST['Observaciones']),
    'ArchivoFisico'       => $_POST['ArchivoFisico'],
    'IdProceso'           => $id,
  ];

  if ($detalle_id) {
    $wpdb->update($tabla_detalle, $data_d, ['Id' => $detalle_id]);
  } else {
    $data_d['Activo'] = 1;
    $data_d['FechaCreacion'] = current_time('mysql');
    $data_d['IdUserCreation']  = get_current_user_id();
    $wpdb->insert($tabla_detalle, $data_d);
  }
  
    $cambios = [];


    foreach ($data_p as $campo => $valor_nuevo) {
        $valor_actual = $proceso->$campo ?? null;
        if ($valor_actual != $valor_nuevo) {
            $cambios[] = [
                'tabla' => 'Proceso',
                'campo' => $campo,
                'antes' => $valor_actual,
                'despues' => $valor_nuevo
            ];
        }
    }

    // Comparar cambios en Detalle
    foreach ($data_d as $campo => $valor_nuevo) {
        $valor_actual = $detalle->$campo ?? null;
        if ($campo != 'IdProceso' && $valor_actual != $valor_nuevo) {
            $cambios[] = [
                'tabla' => 'Detalle',
                'campo' => $campo,
                'antes' => $valor_actual,
                'despues' => $valor_nuevo
            ];
        }
    }
    
    // Si hubo cambios, enviar correo
    if (!empty($cambios)) {
        $to = ["solucionestegnologicasga@gmail.com", "gerencia@galogistic.com"];
        $subject = "Cambios en el proceso ID: $id";
    
        $mensaje = "<h3>Se detectaron cambios en el proceso #$id</h3>"; 
        $mensaje .= "<h4>Hora de edición: " . date("Y-m-d H:i:s") . "</h4>\n";
        $mensaje .= "<table border='1' cellspacing='0' cellpadding='5'>
                        <tr><th>Tabla</th><th>Campo</th><th>Antes</th><th>Después</th></tr>";
        foreach ($cambios as $c) {
            $mensaje .= "<tr>
                            <td>{$c['tabla']}</td>
                            <td>{$c['campo']}</td>
                            <td>{$c['antes']}</td>
                            <td>{$c['despues']}</td>
                         </tr>";
        }
        $mensaje .= "</table>";
    
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        wp_mail($to, $subject, $mensaje, $headers);
    }

  // Redirigir para evitar reenvío
  wp_safe_redirect(add_query_arg(['view' => 'bitacora_detalle', 'id' => $id], $_SERVER['PHP_SELF']));
  exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear_transporte') {
  $dataEntrada = [];
  $dataDetalle = [];
  $idProceso = intval($_POST['idProceso']);
  $idEntrada = intval($_POST['IdTipoEntrada']);
  $tabla = 'bc_entrada_bitacora_transporte';
  $tablaEntrada = 'bc_entrada_bitacora';

  // Auditoría
  //echo "<script>console.log(" . json_encode($_POST) . ");</script>";


  $dataEntrada = [
    'IdTipoEntrada'       => $idEntrada,
    'IdProceso'               => $idProceso,
    'IdUser'               => get_current_user_id(),
    'FechaCreacion'     => current_time('mysql'),
    'Activo'  => 1
  ];

  
  // Insertar
  $insertedEntrada = $wpdb->insert($tablaEntrada, $dataEntrada);

  if ($insertedEntrada) {
    $new_id = $wpdb->insert_id;
    $response = ['success' => false, 'data' => ''];

    $dataDetalle = [
      'Descripcion'   => sanitize_text_field($_POST['descripcion']),
      'Manifiesto'   => sanitize_text_field($_POST['manifiestoEntrada']),
      'IdEntradaBitacora'       => $new_id,
      'CiudadDestino'       => sanitize_text_field($_POST['ciudadDestino']),
      'Documentacion'         => sanitize_text_field($_POST['documentacion']),
      'CobroCliente'     => sanitize_text_field($_POST['cobroCliente']),
      'TamanoContenedor' => sanitize_text_field($_POST['tamanoContenedor']),
      'NumeroContenedor' => sanitize_text_field($_POST['numeroContenedor']),
      'Conductor' => sanitize_text_field($_POST['conductor']),
      'Placa' => sanitize_text_field($_POST['placa']),
      'Remesa' => sanitize_text_field($_POST['remesa']),
      'FechaElaboracion' => sanitize_text_field($_POST['fechaElaboracion']),
      'FechaSalidaPuerto' => sanitize_text_field($_POST['fechaSalidaPuerto']),
      'FechaEntregaUnidadVacia' => sanitize_text_field($_POST['fechaEntregaUnidadVacia'])
    ];


    // Auditoría

    //$data['IdUser']     = get_current_user_id();
    $dataDetalle['FechaCreacion'] = current_time('mysql');
    $dataDetalle['Activo']     = 1;
    //echo "<script>console.log(" . json_encode($dataDetalle) . ");</script>";

    // Insertar
    $wpdb->show_errors(); // Activar errores SQL
    $inserted = $wpdb->insert($tabla, $dataDetalle);

    if ($inserted) {
      $response['success'] = true;
      $response['data'] = 'Entrada y detalle creados correctamente.';
    } else {
      $response['data'] = 'Error al crear detalle: ' . $wpdb->last_error;
      error_log('Error SQL: ' . $wpdb->last_error);
    }
  } else {
   $response['data'] = 'Error al crear la entrada: ' . $wpdb->last_error;
  }
   // Devuelve JSON válido
  wp_send_json($response);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear_giros') {
  $dataEntrada = [];
  $dataDetalle = [];
  $idProceso = intval($_POST['idProceso']);
  $idEntrada = intval($_POST['IdTipoEntrada']);
  $tabla = 'bc_entrada_bitacora_giro';
  $tablaEntrada = 'bc_entrada_bitacora';

  // Auditoría
  $dataEntrada = [
    'IdTipoEntrada'       => $idEntrada,
    'IdProceso'               => $idProceso,
    'IdUser'               => get_current_user_id(),
    'FechaCreacion'     => current_time('mysql'),
    'Activo'  => 1
  ];

  //echo "<script>console.log(" . json_encode($dataEntrada) . ");</script>";
  // Insertar
  $insertedEntrada = $wpdb->insert($tablaEntrada, $dataEntrada);

  if ($insertedEntrada) {
    $new_id = $wpdb->insert_id;
    $response = ['success' => false, 'data' => ''];

    $dataDetalle = [
      'Descripcion'   => sanitize_text_field($_POST['descripcion']),
      'ComprobanteSiigo'   => sanitize_text_field($_POST['ComprobanteSiigo']),
      'IdEntradaBitacora'       => $new_id,
      'FechaElaboracion'       => sanitize_text_field($_POST['FechaElaboracion']),
      'NombreTercero'         => sanitize_text_field($_POST['NombreTercero']),
      'DescripcionMovimiento'     => sanitize_text_field($_POST['DescripcionMovimiento']),
      'Debito' => sanitize_text_field($_POST['Debito']),
      'DOCruzado' => sanitize_text_field($_POST['DOCruzado']),
      'IdEstado' => sanitize_text_field($_POST['Estado']),
      'NumeroDeclaracion' => sanitize_text_field($_POST['NumeroDeclaracion']),
      'USDFOB' => sanitize_text_field($_POST['USDFOB']),
      'USDDeclaradoConFlete' => sanitize_text_field($_POST['USDDeclaradoConFlete']),
      'USDReal' => sanitize_text_field($_POST['USDReal']),
      'FechaMovimiento' => sanitize_text_field($_POST['FechaMovimiento']),
      'Proveedor' => sanitize_text_field($_POST['Proveedor'])

    ];

    // Auditoría

    //$data['IdUser']     = get_current_user_id();
    $dataDetalle['FechaCreacion'] = current_time('mysql');
    $dataDetalle['Activo']     = 1;
    
    // Insertar
    $wpdb->show_errors(); // Activar errores SQL
    $inserted = $wpdb->insert($tabla, $dataDetalle);

    if ($inserted) {
      $response['success'] = true;
      $response['data'] = 'Entrada y detalle creados correctamente.';
    } else {
      $response['data'] = 'Error al crear detalle: ' . $wpdb->last_error;
      error_log('Error SQL: ' . $wpdb->last_error);
    }
  } else {
   $response['data'] = 'Error al crear la entrada: ' . $wpdb->last_error;
  }
   // Devuelve JSON válido
  wp_send_json($response);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear_contabilidad') {
  $dataEntrada = [];
  $dataDetalle = [];
  $idProceso = intval($_POST['idProceso']);
  $idEntrada = intval($_POST['IdTipoEntrada']);
  $tabla = 'bc_entrada_bitacora_contabilidad';
  $tablaEntrada = 'bc_entrada_bitacora';

  // Auditoría
  $dataEntrada = [
    'IdTipoEntrada'       => $idEntrada,
    'IdProceso'               => $idProceso,
    'IdUser'               => get_current_user_id(),
    'FechaCreacion'     => current_time('mysql'),
    'Activo'  => 1
  ];
  
  // Insertar
  $insertedEntrada = $wpdb->insert($tablaEntrada, $dataEntrada);

  if ($insertedEntrada) {
    $new_id = $wpdb->insert_id;
     $response = ['success' => false, 'data' => ''];

    $dataDetalle = [
      'Descripcion'   => sanitize_text_field($_POST['descripcion']),
      'NombreClienteProveedor'   => sanitize_text_field($_POST['NombreClienteProveedor']),
      'IdEntradaBitacora'       => $new_id,
      'FechaDocumento'       => sanitize_text_field($_POST['FechaDocumento']),
      'IdTipoDocumento' => sanitize_text_field($_POST['IdTipoDocumento']),
      'IdTipoDocumentoContabilidad' => sanitize_text_field($_POST['IdTipoDocumentoContabilidad']),
    ];

    // Auditoría
    $dataDetalle['FechaIngresoSistema'] = current_time('mysql');
    $dataDetalle['FechaVencimiento'] = current_time('mysql');
    $dataDetalle['FechaCreacion'] = current_time('mysql');
    $dataDetalle['Activo'] = 1;

    // Insertar
    $wpdb->show_errors(); // Activar errores SQL
    $inserted = $wpdb->insert($tabla, $dataDetalle);

    if ($inserted) {
      $response['success'] = true;
      $response['data'] = 'Entrada y detalle creados correctamente.';
    } else {
      $response['data'] = 'Error al crear detalle: ' . $wpdb->last_error;
      error_log('Error SQL: ' . $wpdb->last_error);
    }
  } else {
   $response['data'] = 'Error al crear la entrada: ' . $wpdb->last_error;
  }
   // Devuelve JSON válido
  wp_send_json($response);

}
// Leer el ID del proceso que viene por URL
$id = isset($_GET['id']) ? intval($_GET['id']) : (
  isset($_POST['id']) ? intval($_POST['id']) : 0);
if (!$id) {
  echo '<p>Proceso no válido.</p>';
  return;
}

// Consultar datos del proceso (incluye creador, cliente, importador y estado)
$sql = $wpdb->prepare(
  "SELECT
          p.*, 
          u.user_login AS creador,
          c1.RazonSocial AS Cliente,
          c2.RazonSocial AS Importador,
          ep.Descripcion AS EstadoDescripcion,
          ep.Color       AS EstadoColor,
          cat_tipo.Descripcion AS TipoProcesoDesc,
          cat_dig.Descripcion AS DigitacionRevisionDesc,
          cat_aduana.Descripcion AS AduanaDesc,
          cat_pies.Descripcion AS PiesDesc,
          cat_puerto.Descripcion AS PuertoDesc
      FROM {$tabla} p
      LEFT JOIN {$wpdb->prefix}users u
        ON u.ID = p.IdUserCreation
      LEFT JOIN {$tabla_clientes} c1
        ON c1.Id = p.IdCliente
      LEFT JOIN {$tabla_clientes} c2
        ON c2.Id = p.IdImportador
      LEFT JOIN {$tabla_estados} ep
        ON ep.Id = p.IdEstadoProceso
      LEFT JOIN bc_catalogo cat_tipo
        ON cat_tipo.Id = p.IdTipoProceso
      LEFT JOIN bc_catalogo cat_dig
        ON cat_dig.Id = p.IdDigitacionRevision
      LEFT JOIN bc_catalogo cat_aduana
        ON cat_aduana.Id = p.IdAduana
      LEFT JOIN bc_catalogo cat_pies
        ON cat_pies.Id = p.IdPies
      LEFT JOIN bc_catalogo cat_puerto
        ON cat_puerto.Id = p.IdPuerto
      WHERE p.Id = %d",
  $id
);
$proceso = $wpdb->get_row($sql);
if (!$proceso) {
  echo '<p>Proceso no encontrado.</p>';
  return;
}

// Detalles relacionados
$detalles = $wpdb->get_results(
  $wpdb->prepare(
    "SELECT * FROM {$tabla_detalle} WHERE IdProceso = %d ORDER BY Id",
    $id
  )
);

$detalle = ! empty($detalles) ? $detalles[0] : null;

// Listas para selects
$clientes = $wpdb->get_results("SELECT Id, RazonSocial FROM {$tabla_clientes} WHERE Activo=1 ORDER BY RazonSocial");
$importadores = $clientes; // mismos registros, diferencia según flujo
$estadosList = $wpdb->get_results("SELECT Id, Descripcion FROM {$tabla_estados} WHERE Activo=1 ORDER BY Id");

$tipos = $wpdb->get_results("SELECT Id, Descripcion FROM bc_catalogo WHERE Tipo='TipoProceso' AND Activo=1 ORDER BY Descripcion");
$digitaciones = $wpdb->get_results("SELECT Id, Descripcion FROM bc_catalogo WHERE Tipo='DigitacionRevision' AND Activo=1 ORDER BY Descripcion");
$aduanas = $wpdb->get_results("SELECT Id, Descripcion FROM bc_catalogo WHERE Tipo='Aduana' AND Activo=1 ORDER BY Descripcion");
$pies = $wpdb->get_results("SELECT Id, Descripcion FROM bc_catalogo WHERE Tipo='Pies' AND Activo=1 ORDER BY Descripcion");
$puertos = $wpdb->get_results("SELECT Id, Descripcion FROM bc_catalogo WHERE Tipo='Puerto' AND Activo=1 ORDER BY Descripcion");

$tipos_entrada = $wpdb->get_results(
  "SELECT Id, Descripcion 
   FROM {$tabla_tipo_entrada} 
   WHERE Activo = 1 
   ORDER BY Descripcion"
);

function dt_local_value($val) {
    if (empty($val) || $val === '0000-00-00 00:00:00') return '';
    $ts = strtotime($val);
    if ($ts === false) return '';
    return date('Y-m-d\TH:i', $ts);
}

function disabled_if_24h_passed($datetime) {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return ''; // no bloquear si está vacío
    }
    $filled_time = strtotime($datetime);
    if ($filled_time && (time() - $filled_time >= 24 * 3600)) {
        return 'readonly';
    }
    return '';
}
?>
<script src="/wp-content/bitacoras/assets/js/common-loader.js"></script>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Entradas Bitácora</title>
  <link rel="stylesheet" href="styles/style.css">
</head>

<body>
  <h1>Entradas Bitácora</h1>

  <!-- Sección de resumen: una sola tarjeta con múltiples datos -->
  <input type="checkbox" id="popup-toggle-edit" hidden>
  <div class="summary-card single">
  <?php if ($usuario->rol_codigo !== 'CLI') : ?>
    <label for="popup-toggle-edit" class="edit-icon" title="Editar Proceso">⚙️</label>
  <?php endif; ?>
    <h2>Información del Proceso</h2>
    <div class="summary-grid">
      <div><strong>DO:</strong> <?= esc_html($proceso->DO) ?></div>
      <div><strong>Cliente:</strong> <?= esc_html($proceso->Cliente) ?></div>
      <div><strong>Importador:</strong> <?= esc_html($proceso->Importador) ?></div>
      <div><strong>Estado:</strong> <span class="status-label" style="background-color: <?= esc_attr($proceso->EstadoColor) ?>;"><?= esc_html($proceso->EstadoDescripcion) ?></span></div>
      <div><strong>Creado el:</strong> <?= date('d/m/Y', strtotime($proceso->FechaCreacion)) ?></div>
      <div><strong>Creador:</strong> <?= esc_html($proceso->creador) ?></div>
      <div><strong>Tipo Proceso:</strong> <?= esc_html($proceso->TipoProcesoDesc) ?></div>
      <div><strong>DO Agencia:</strong> <?= esc_html($proceso->DOAgencia) ?></div>
      <div><strong>Agente Carga:</strong> <?= esc_html($proceso->AgenteCarga) ?></div>
      <div><strong>ETA:</strong> <?= date('d/m/Y', strtotime($proceso->ETA)) ?></div>
      <div><strong>Días Libres:</strong> <?= esc_html($proceso->DiasLibres) ?></div>
      <div><strong>Digitación/Revision:</strong> <?= esc_html($proceso->DigitacionRevisionDesc) ?></div>
    </div>
  </div>


  <input type="checkbox" id="popup-toggle-add" hidden>
<?php if ($usuario->rol_codigo === 'ADMIN' || $usuario->rol_codigo === 'GIRO' || $usuario->rol_codigo === 'TRANS' || $usuario->rol_codigo === 'CONT') : ?>
  <div class="toolbar" style="display:flex; justify-content:flex-end; gap:10px; margin-bottom:20px;">
    <label for="popup-toggle-add" class="btn" style="display: flex; align-items: center; gap: 5px;">        
        <svg class="w-[18px] h-[18px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5" />
        </svg> 
        <div>Nueva Entrada</div>
        </label>
  </div>
<?php endif; ?>
  <!-- Popup de edición/formulario completo -->
  <div class="overlay-edit">
    <div class="modal-container">
      <h3>Editar Proceso</h3>
      <?php
      $is_admin = ($usuario->rol_codigo === 'ADMIN');
      $rd_attr  = $is_admin ? '' : 'readonly'; // para inputs
      $ds_attr  = $is_admin ? '' : 'disabled'; // para selects
      ?>

      <form method="post" action="?view=bitacora_detalle&id=<?= esc_attr($proceso->Id) ?>" class="popup-grid-5">
        <?php wp_nonce_field('editar_proceso_action', 'editar_proceso_nonce'); ?>

        <!-- Campos principales -->
        <input type="hidden" name="detalle_id" value="<?= esc_attr($detalle ? $detalle->Id : '') ?>">
        <div class="form-group"><label for="DO">DO:</label>
          <input readonly type="text" id="DO" name="DO" value="<?= esc_attr($proceso->DO) ?>" <?= $rd_attr ?>>
        </div>
        <div class="form-group">
          <label for="IdCliente">Cliente:</label>
          <select id="IdCliente" name="IdCliente" <?= $ds_attr ?>>
            <?php foreach ($clientes as $c): ?>
              <option value="<?= $c->Id ?>" <?= selected($proceso->IdCliente, $c->Id, false) ?>>
                <?= esc_html($c->RazonSocial) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (!$is_admin): ?>
            <input type="hidden" name="IdCliente" value="<?= esc_attr($proceso->IdCliente) ?>">
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label for="IdImportador">Importador:</label>
          <select id="IdImportador" name="IdImportador" <?= $ds_attr ?>>
            <?php foreach ($importadores as $imp): ?>
              <option value="<?= $imp->Id ?>" <?= selected($proceso->IdImportador, $imp->Id, false) ?>>
                <?= esc_html($imp->RazonSocial) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (!$is_admin): ?>
            <input type="hidden" name="IdImportador" value="<?= esc_attr($proceso->IdImportador) ?>">
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label for="IdEstadoProceso">Estado:</label>
          <select disabled id="IdEstadoProceso" name="IdEstadoProceso_disabled">
            <?php foreach ( $estadosList as $st ): ?>
              <option value="<?= esc_attr( $st->Id ) ?>"
                <?= selected( $proceso->IdEstadoProceso, $st->Id, false ) ?>>
                <?= esc_html( $st->Descripcion ) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <!-- Campo oculto para que el valor enviado sea el correcto -->
          <input type="hidden" id="IdEstadoProceso" name="IdEstadoProceso"
                value="<?= esc_attr( $proceso->IdEstadoProceso ) ?>">
        </div>

        <div class="form-group"><label for="FechaCreacion">Fecha Creación:</label>
          <input readonly type="date" id="FechaCreacion" name="FechaCreacion" value="<?= esc_attr(date('Y-m-d', strtotime($proceso->FechaCreacion))) ?>" <?= $rd_attr ?>>
        </div>

        <!-- Campos adicionales -->
        <div class="form-group"><label for="IdTipoProceso">Tipo Proceso:</label>
          <select id="IdTipoProceso" name="IdTipoProceso" <?= $ds_attr ?>>
            <option value="">Seleccione...</option>
            <?php foreach($tipos as $t): ?>
              <option value="<?= $t->Id ?>" <?= selected($proceso->IdTipoProceso, $t->Id, false) ?>>
                <?= esc_html($t->Descripcion) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (!$is_admin): ?>
            <input type="hidden" name="IdTipoProceso" value="<?= esc_attr($proceso->IdTipoProceso) ?>">
          <?php endif; ?>
        </div>
        <div class="form-group"><label for="DOAgencia">DO Agencia:</label>
          <input type="text" id="DOAgencia" name="DOAgencia" value="<?= esc_attr($proceso->DOAgencia) ?>" <?= $rd_attr ?>>
        </div>
        <div class="form-group"><label for="AgenteCarga">Agente de carga/Naviera:</label>
          <input type="text" id="AgenteCarga" name="AgenteCarga" value="<?= esc_attr($proceso->AgenteCarga) ?>" <?= $rd_attr ?>>
        </div>
        <div class="form-group"><label for="ETA">ETA:</label>
          <input type="datetime-local" id="ETA" name="ETA" value="<?= esc_attr(date('Y-m-d\TH:i', strtotime($proceso->ETA))) ?>" <?= $rd_attr ?>>
        </div>

        <div class="form-group"><label for="DiasLibres">Días Libres:</label>
          <input type="text" id="DiasLibres" name="DiasLibres" value="<?= esc_attr($proceso->DiasLibres) ?>" <?= $rd_attr ?>>
        </div>

        <div class="form-group"><label for="IdDigitacionRevision">Digitación/Revisión:</label>
          <select id="IdDigitacionRevision" name="IdDigitacionRevision" <?= $ds_attr ?>>
            <option value="">Seleccione...</option>
            <?php foreach($digitaciones as $d): ?>
              <option value="<?= $d->Id ?>" <?= selected($proceso->IdDigitacionRevision, $d->Id, false) ?>>
                <?= esc_html($d->Descripcion) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (!$is_admin): ?>
            <input type="hidden" name="IdDigitacionRevision" value="<?= esc_attr($proceso->IdDigitacionRevision) ?>">
          <?php endif; ?>
        </div>

        <div class="form-group"><label for="IdAduana">Aduana:</label>
          <select id="IdAduana" name="IdAduana" <?= $ds_attr ?>>
            <option value="">Seleccione...</option>
            <?php foreach($aduanas as $a): ?>
              <option value="<?= $a->Id ?>" <?= selected($proceso->IdAduana, $a->Id, false) ?>>
                <?= esc_html($a->Descripcion) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (!$is_admin): ?>
            <input type="hidden" name="IdAduana" value="<?= esc_attr($proceso->IdAduana) ?>">
          <?php endif; ?>
        </div>

        <div class="form-group"><label for="Producto">Producto:</label>
          <input type="text" id="Producto" name="Producto" value="<?= esc_attr($proceso->Producto) ?>" <?= $rd_attr ?>>
        </div>

        <div class="form-group"><label for="NumeroBL">Número BL:</label>
          <input type="text" id="NumeroBL" name="NumeroBL" value="<?= esc_attr($proceso->NumeroBL) ?>" <?= $rd_attr ?>>
        </div>

        <div class="form-group"><label for="Contenedor">Contenedor:</label>
          <input type="text" id="Contenedor" name="Contenedor" value="<?= esc_attr($proceso->Contenedor) ?>" <?= $rd_attr ?>>
        </div>

        <div class="form-group"><label for="IdPuerto">Puerto:</label>
          <select id="IdPuerto" name="IdPuerto" <?= $ds_attr ?>>
            <option value="">Seleccione...</option>
            <?php foreach($puertos as $pt): ?>
              <option value="<?= $pt->Id ?>" <?= selected($proceso->IdPuerto, $pt->Id, false) ?>>
                <?= esc_html($pt->Descripcion) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (!$is_admin): ?>
            <input type="hidden" name="IdPuerto" value="<?= esc_attr($proceso->IdPuerto) ?>">
          <?php endif; ?>
        </div>

        <div class="form-group"><label for="IdPies">Pies:</label>
          <select id="IdPies" name="IdPies" <?= $ds_attr ?>>
            <option value="">Seleccione...</option>
            <?php foreach($pies as $p): ?>
              <option value="<?= $p->Id ?>" <?= selected($proceso->IdPies, $p->Id, false) ?>>
                <?= esc_html($p->Descripcion) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (!$is_admin): ?>
            <input type="hidden" name="IdPies" value="<?= esc_attr($proceso->IdPies) ?>">
          <?php endif; ?>
        </div>

        <div class="form-group"><label for="Bulto">Bulto:</label>
          <input type="text" id="Bulto" name="Bulto" value="<?= esc_attr($proceso->Bulto) ?>" <?= $rd_attr ?>>
        </div>

        <div class="form-group"><label for="PesoBruto">Peso Bruto:</label>
          <input type="text" id="PesoBruto" name="PesoBruto" value="<?= esc_attr($proceso->PesoBruto) ?>" <?= $rd_attr ?>>
        </div>

        <div class="form-group"><label for="Bandera">Bandera:</label>
          <input type="text" id="Bandera" name="Bandera" value="<?= esc_attr($proceso->Bandera) ?>" <?= $rd_attr ?>>
        </div>

        <!-- Campos de detalle: siempre se muestran -->
        <div class="form-group">
          <label for="PagoNaviera">Fecha Pago Naviera:</label>
          <input type="datetime-local" id="PagoNaviera" name="PagoNaviera"
                value="<?= esc_attr(dt_local_value($detalle->PagoNaviera ?? null)) ?>"
                <?= disabled_if_24h_passed($detalle->PagoNaviera ?? null) ?>>
        </div>
        <div class="form-group">
          <label for="Liberacion">Liberación:</label>
          <input type="datetime-local" id="Liberacion" name="Liberacion"
                value="<?= esc_attr(dt_local_value($detalle->Liberacion ?? null)) ?>"
                <?= disabled_if_24h_passed($detalle->Liberacion ?? null) ?>>
        </div>
        <div class="form-group">
          <label for="Aceptacion">Aceptación:</label>
          <input type="datetime-local" id="Aceptacion" name="Aceptacion"
                value="<?= esc_attr(dt_local_value($detalle->Aceptacion ?? null)) ?>"
                <?= disabled_if_24h_passed($detalle->Aceptacion ?? null) ?>>
        </div>
        <div class="form-group">
          <label for="Pago">Pago Impuestos:</label>
          <input type="datetime-local" id="Pago" name="Pago"
                value="<?= esc_attr(dt_local_value($detalle->Pago ?? null)) ?>"
                <?= disabled_if_24h_passed($detalle->Pago ?? null) ?>>
        </div>        
        <div class="form-group">
          <label for="Selectividad">Selectividad:</label>
          <input type="datetime-local" id="Selectividad" name="Selectividad"
                value="<?= esc_attr(dt_local_value($detalle->Selectividad ?? null)) ?>"
                <?= disabled_if_24h_passed($detalle->Selectividad ?? null) ?>>
        </div>
        <div class="form-group">
          <label for="Levante">Levante:</label>
          <input type="datetime-local" id="Levante" name="Levante"
                value="<?= esc_attr(dt_local_value($detalle->Levante ?? null)) ?>"
                <?= disabled_if_24h_passed($detalle->Levante ?? null) ?>>
        </div>
        <div class="form-group">
          <label for="EntregaTransporte">Entrega Transporte:</label>
          <input type="datetime-local" id="EntregaTransporte" name="EntregaTransporte"
                value="<?= esc_attr(dt_local_value($detalle->EntregaTransporte ?? null)) ?>"
                <?= disabled_if_24h_passed($detalle->EntregaTransporte ?? null) ?>>
        </div>
        <div class="form-group"><label for="Manifiesto">Manifiesto:</label>
          <input type="text" id="Manifiesto" name="Manifiesto" value="<?= esc_attr($detalle->Manifiesto ?? '') ?>">
        </div>
        <div class="form-group"><label for="Observaciones">Observaciones:</label>
          <input type="text" id="Observaciones" name="Observaciones" value="<?= esc_attr($detalle->Observaciones ?? '') ?>">
        </div>
        <div class="form-group"><label for="Deposito">Depósito:</label>
          <input type="text" id="Deposito" name="Deposito" value="<?= esc_attr($detalle->Deposito ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="DevolucionUnidad">Devolución Unidad:</label>
          <input type="datetime-local" id="DevolucionUnidad" name="DevolucionUnidad"
                value="<?= esc_attr(dt_local_value($detalle->DevolucionUnidad ?? null)) ?>"
                <?= disabled_if_24h_passed($detalle->DevolucionUnidad ?? null) ?>>
        </div>
        <div class="form-group">
          <label for="ArchivoFisico">Archivo Físico:</label>
          <!-- campo oculto con valor por defecto -->
          <input type="hidden" name="ArchivoFisico" value="0">
          <!-- checkbox real -->
          <input type="checkbox" id="ArchivoFisico" name="ArchivoFisico" value="1" <?= !empty($detalle->ArchivoFisico) ? 'checked' : '' ?>>
        </div>
        <!-- Acciones -->
         
        <div class="popup-actions" style="grid-column:1 / -1; display:flex; justify-content:flex-end; gap:10px;">
          
          <label for="popup-toggle-edit" class="btn close">Cancelar</label>
          
          <?php if ($usuario->rol_codigo === 'ADMIN' || $usuario->rol_codigo === 'IMPOR' || $usuario->rol_codigo === 'TRANS') : ?>
          <button type="submit" class="btn">Guardar</button>
          <?php endif; ?>
        </div>
        
      </form>
    </div>
  </div>


  <!-- Overlay y popup de Crear Entrada -->
  <div class="overlay-add">
    <div class="popup-add">
      <h3>Crear Nueva Entrada</h3>
      <form id="entrada-add-form" onsubmit="showLoader()">
        <div class="form-grid">
          <!-- Tipo de Entrada -->
          <input type="hidden" name="idProceso" value="<?= esc_attr($id) ?>">
          <div class="form-group">
            <label for="IdTipoEntrada">Tipo de Entrada</label>
            <select id="IdTipoEntrada" name="IdTipoEntrada" required>
              <option value="">— Seleccione un tipo —</option>
              <?php foreach ($tipos_entrada as $t): ?>
                <option value="<?= esc_attr($t->Id) ?>">
                  <?= esc_html($t->Descripcion) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Subir Documentos -->
          <!-- <div class="form-row">
            <label for="documentos">Subir Documentos</label>
            <input type="file" id="documentos" name="documentos[]" multiple>
          </div> -->

        </div>


        <div id="formulario-popup-container-add" class="form-grid"></div>
        <div id="loader-overlay">
          <div class="spinner"></div>
        </div>



      </form>
      <div class="form-buttons">
        <a href="?view=bitacora_detalle&id=<?= $id ?>" class="btn close">Cerrar</a>
        <button id="btn-add-guardar" class="btn">Crear</button>
      </div>

    </div>
  </div>

  <ul class="tabs">
    <?php if ($usuario->rol_codigo === 'ADMIN' || $usuario->rol_codigo === 'GIRO' || $usuario->rol_codigo === 'TRANS' || $usuario->rol_codigo === 'CONT' || $usuario->rol_codigo === 'IMPOR') : ?>
      <li data-tab="tab-contabilidad" data-tipo="CTB" class="active">Contabilidad</li>
      <li data-tab="tab-giros" data-tipo="GRO">Giros</li>
    <?php endif; ?>
    <li data-tab="tab-transporte" data-tipo="TRS">Transporte</li>
  </ul>

  <!-- Contenido de cada pestaña -->
  <div id="tab-contabilidad" class="tab-content active" data-tipo="CTB"></div>
  <div id="tab-giros" class="tab-content" data-tipo="GRO"></div>
  <div id="tab-transporte" class="tab-content" data-tipo="TRS"></div>
  </div>
</body>

</html>

<?php include_once(__DIR__ . '/bitacora_detalle_filtro.php'); ?>
<script>
  document.getElementById('btn-add-guardar').addEventListener('click', () => {
    const formData = new FormData(document.getElementById('entrada-add-form'));

    switch ($sufijo_entrada) {
      case 'TRS':
        formData.append('action', 'crear_transporte');
        break;
      case 'GRO':
        formData.append('action', 'crear_giros');
        break;
      case 'CTB':
        formData.append('action', 'crear_contabilidad');
        break;
      default:
        console.warn('Tipo de entrada no reconocido:', $sufijo_entrada);
    }




    showLoader();
    fetch('bitacora_detalle.php', {
        method: 'POST',
        body: formData
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          Swal.fire({
            icon: 'success',
            title: 'Creado',
            text: 'La entrada fue creada correctamente.',
            confirmButtonText: 'OK'
          }).then(() => {
            showLoader();
            location.reload();
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: res.data || 'Ocurrió un error inesperado.'
          });
        }
      }).finally(hideLoader);
  });
  document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.tabs li');
    const contents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        contents.forEach(c => c.classList.remove('active'));

        tab.classList.add('active');
        const tabId = tab.getAttribute('data-tab');
        const tipo = tab.getAttribute('data-tipo');

        const contenedor = document.getElementById(tabId);
        contenedor.classList.add('active');

        if (!contenedor.dataset.loaded && typeof cargarEntradas === 'function') {
          cargarEntradas(tipo, tabId);
          contenedor.dataset.loaded = 'true';
        }
      });
    });

    const sidebarLinks = document.querySelectorAll('.form-buttons a'); 
    addLoaderToLinks(sidebarLinks);

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

    // Cargar primer tab automáticamente
    const initialTab = document.querySelector('.tabs li.active');
    if (initialTab) initialTab.click();
  });

  function cargarFormularioPorTipoNuevo(tipoTab) {
    const rutas = {
      CTB: '/wp-content/bitacoras/entradas/nuevo-contabilidad.php',
      GRO: '/wp-content/bitacoras/entradas/nuevo-giros.php',
      TRS: '/wp-content/bitacoras/entradas/nuevo-transporte.php'
    };

    const ruta = rutas[tipoTab];
    if (!ruta) {
      document.getElementById('formulario-popup-container-add').innerHTML = '<p>Formulario no disponible.</p>';
      return;
    }

    showLoader();
    fetch(ruta)
      .then(res => res.text())
      .then(html => {
        document.getElementById('formulario-popup-container-add').innerHTML = html;
        if (tipoTab === 'GRO') {
            setTimeout(() => cargarEstadosGiros(), 50);
          }

      }).finally(hideLoader)
      .catch(err => {
        console.error('Error cargando formulario:', err);
        document.getElementById('formulario-popup-container-add').innerHTML = '<p>Error al cargar formulario.</p>';
      });
  }

  document.getElementById('IdTipoEntrada').addEventListener('change', function() {
    // ocultar todos los bloques
    document.querySelectorAll('.entry-fields').forEach(div => {
      div.style.display = 'none';
    });
    // según el valor del select, mostramos el div correspondiente
    $tipoEntrada = this.value;
    if (!$tipoEntrada) return;
    // mapeo de IDs a sufijos de bloque (ajusta los IDs según tu tabla)
    const map = {
      '1': 'CTB',
      '2': 'GRO',
      '3': 'TRS'
    };
    $sufijo_entrada = map[$tipoEntrada];
    cargarFormularioPorTipoNuevo($sufijo_entrada);
  });

  (function() {
    // Checkbox que controla el modal
    const toggleAdd = document.getElementById('popup-toggle-add');
    // El form dentro del modal “Crear Nueva Entrada”
    const formAdd = document.querySelector('.popup-add form');
    // Todos los bloques de campos extra
    const extras = document.querySelectorAll('.formulario-popup-container-add');

    toggleAdd.addEventListener('change', () => {
      if (!toggleAdd.checked) {
        // 1) Ocultar TODOS los bloques de campos extra
        extras.forEach(div => div.style.display = 'none');
        // 2) Resetear TODO el formulario (select, inputs, textarea, file inputs...)
        formAdd.reset();
      }
    });

    // Opcional: si quieres que al abrir también esté limpio,
    // puedes disparar manualmente el handler al cargar la página:
    if (!toggleAdd.checked) {
      extras.forEach(div => div.style.display = 'none');
      formAdd.reset();
    }
  })();

</script>