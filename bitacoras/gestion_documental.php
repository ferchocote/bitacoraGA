<?php
// Controlador para la vista principal de Gestión Documental (lista y nuevo)
require_once __DIR__ . '/includes/suscripcion.php';
require_once __DIR__ . '/../gestionDocumental/includes/usecases/ListarImportadoresConGestion.php';
require_once __DIR__ . '/../gestionDocumental/includes/usecases/CrearGestionDocumental.php';
require_once __DIR__ . '/../gestionDocumental/includes/usecases/ValidarGestionDocumentalDuplicada.php';
require_once __DIR__ . '/../gestionDocumental/includes/repositories/ImportadorRepository.php';

global $wpdb;

$action = isset($_GET['action']) ? $_GET['action'] : '';
$mensaje = '';
$suscripcionBloqueada = bc_suscripcion_esta_bloqueada($wpdb);
$mensajeSuscripcion = bc_suscripcion_mensaje_bloqueo();

if ($action === 'nueva') {
    // Mostrar formulario para nueva gestión documental
    $repoImportador = new ImportadorRepository($wpdb);
    $importadores = $repoImportador->getTodosImportadores();

    $exito = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($suscripcionBloqueada) {
            $mensaje = '';
        } else {
            $idImportador = (int)($_POST['importador'] ?? 0);
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
                        // Limpiar POST para evitar reenvío
                        $_POST = [];
                    } else {
                        $mensaje = 'Error al crear la gestión.';
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

// Vista por defecto: lista de gestiones documentales
// Obtener filtro de búsqueda si existe
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// Paginación
$per_page = 10;
$page = max(1, intval($_GET['paged'] ?? 1));
$offset = ($page - 1) * $per_page;

// Total de gestiones documentales
$total_gestiones = $wpdb->get_var("SELECT COUNT(*) FROM bc_gestion_documental");

// Importadores paginados
$importadores = $wpdb->get_results($wpdb->prepare(
    "SELECT gd.*, gd.ID AS GestionID, c.RazonSocial, c.NumeroDocumento
     FROM bc_gestion_documental gd
     LEFT JOIN bc_cliente c ON c.ID = gd.IdImportador
     ORDER BY gd.ID DESC
     LIMIT %d OFFSET %d",
    $per_page, $offset
));

include __DIR__ . '/../gestionDocumental/templates/gestion-lista.php';
