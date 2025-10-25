<?php
define('WP_USE_THEMES', false);
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');

global $wpdb;

header('Content-Type: application/json');

if (!is_user_logged_in()) {
  echo json_encode(['error' => 'No autorizado']);
  exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
  case 'get_departamentos':
    $id_pais = intval($_GET['id_pais']);
    $departamentos = $wpdb->get_results("SELECT * FROM  bc_departamento WHERE IdPais = $id_pais");
     

    echo json_encode($departamentos);
    break;

  case 'get_ciudades':
    $id_departamento = intval($_GET['id_departamento']);
    $ciudades = $wpdb->get_results("SELECT * FROM bc_ciudad WHERE IdDepartamento = $id_departamento");
     

    echo json_encode($ciudades);
    break;

  default:
    echo json_encode(['error' => 'Acción no reconocida']);
}
exit;