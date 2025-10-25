<?php
// Controlador para la vista principal de Gestión Documental (lista y nuevo)
require_once __DIR__ . '/../gestionDocumental/includes/usecases/ListarImportadoresConGestion.php';
require_once __DIR__ . '/../gestionDocumental/includes/usecases/CrearGestionDocumental.php';
require_once __DIR__ . '/../gestionDocumental/includes/usecases/ValidarGestionDocumentalDuplicada.php';
require_once __DIR__ . '/../gestionDocumental/includes/repositories/ImportadorRepository.php';

global $wpdb;

$action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';
$mensaje = '';

if ($action === 'nueva') {
    $repoImportador = new ImportadorRepository($wpdb);
    $importadores = $repoImportador->getTodosImportadores();

    $exito = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['gestion_documental_nonce']) || !wp_verify_nonce($_POST['gestion_documental_nonce'], 'crear_gestion_documental')) {
            $mensaje = 'La solicitud no es válida. Inténtalo de nuevo.';
        } else {
            $idImportador = isset($_POST['importador']) ? (int) $_POST['importador'] : 0;
            $idUsuario = get_current_user_id();

            if ($idImportador > 0) {
                $validador = new ValidarGestionDocumentalDuplicada($wpdb);
                if ($validador->existeGestionActiva($idImportador)) {
                    $mensaje = 'Ya existe una gestión activa para este importador.';
                } else {
                    $nuevaId = crear_gestion_documental($wpdb, $idImportador, $idUsuario);
                    if ($nuevaId) {
                        $exito = true;
                        $mensaje = 'Gestión documental creada exitosamente.';
                        $_POST = [];
                    } else {
                        $mensaje = 'Error al crear la gestión. ' . esc_html($wpdb->last_error);
                    }
                }
            } else {
                $mensaje = 'Seleccione un importador.';
            }
        }
    }

    include __DIR__ . '/../gestionDocumental/templates/nueva-gestion-documental.php';
    return;
}

$q = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
$per_page = 10;
$page = max(1, isset($_GET['paged']) ? (int) $_GET['paged'] : 1);
$offset = ($page - 1) * $per_page;

$listadoUseCase = new ListarImportadoresConGestion($wpdb);
$total_gestiones = $listadoUseCase->contar($q);
$importadores = $listadoUseCase->ejecutar($per_page, $offset, $q);

include __DIR__ . '/../gestionDocumental/templates/gestion-lista.php';
