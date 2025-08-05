<?php

define('WP_USE_THEMES', false);
require_once('../../wp-load.php');

global $wpdb;
$tabla = 'bc_cliente';

// --- MANEJO AJAX INTERNO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'actualizar_cliente') {
    $id = intval($_POST['id']);


    $data = [
        'NumeroDocumento'   => sanitize_text_field($_POST['documento']),
        'RazonSocial'       => sanitize_text_field($_POST['razon_social']),
        'Direccion'         => sanitize_text_field($_POST['direccion']),
        'NumeroCelular'     => sanitize_text_field($_POST['celular']),
        'CorreoElectronico' => sanitize_email($_POST['correo']),
        'ActividadEconomica' => sanitize_text_field($_POST['ActividadEconomica']),
        'IdTipoDocumento' => sanitize_text_field($_POST['IdTipoDocumento']),
        'IdRegimen' => sanitize_text_field($_POST['IdRegimen']),
        'ResponsableIva'   => sanitize_text_field(!array_key_exists('ResponsableIva', $_POST) ? 0 : 1),
        'AplicaRetenciones'   => sanitize_text_field(!array_key_exists('AplicaRetenciones', $_POST) ? 0 : 1),
    ];
    //  echo "<script>console.log(" . json_encode($data) . ");</script>";



    $result = $wpdb->update($tabla, $data, ['Id' => $id]);

    header('Content-Type: application/json');
    if ($result !== false) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'data' => 'No se pudo actualizar.']);
    }
    exit;
}

// Valida rol de usuario 
if ($usuario->rol_codigo != "ADMIN" && $usuario->rol_codigo != "RRHH") {
    echo "No tienes permiso para acceder a esta vista.";
    exit;
}

// Parámetros actuales de la URL
$params = $_GET;

// Capturamos el término de búsqueda
$q = isset($_GET['q']) ? sanitize_text_field( $_GET['q'] ) : '';
$where_clauses = [];
$prepare_params = [];

// Si hay búsqueda, añadimos cláusula
if ( $q !== '' ) {
    $like = '%' . $wpdb->esc_like( $q ) . '%';
    $where_clauses[] = "( td.Descripcion      LIKE %s
                          OR c.NumeroDocumento LIKE %s
                          OR c.RazonSocial     LIKE %s
                          OR c.Direccion       LIKE %s )";
    // empujamos 4 veces el mismo parámetro
    array_push( $prepare_params, $like, $like, $like, $like );
}

// Montamos la parte WHERE
$where_sql = '';
if ( ! empty( $where_clauses ) ) {
    $where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );
}

// 1) Contar total para paginador (sin LIMIT)
$count_sql = "
    SELECT COUNT(*)
      FROM {$tabla} c
      LEFT JOIN bc_tipo_documento td ON td.Id = c.IdTipoDocumento
    {$where_sql}
";
if ( ! empty( $prepare_params ) ) {
    // si tenemos placeholders en WHERE, preparamos
    $total_registros = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $prepare_params ) );
} else {
    // sin parámetros, ejecutamos directo
    $total_registros = (int) $wpdb->get_var( $count_sql );
}

// 2) Paginación
$pagina_actual         = isset( $_GET['pg'] ) ? max(1,intval($_GET['pg'])) : 1;
$registros_por_pagina  = 10;
$offset                = ($pagina_actual - 1) * $registros_por_pagina;
$total_paginas         = ceil( $total_registros / $registros_por_pagina );

// 3) Consulta paginada (añadimos LIMIT y OFFSET a los parámetros)
$prepare_params[] = $registros_por_pagina;
$prepare_params[] = $offset;

// Consulta paginada
$select_sql = "
    SELECT 
    c.Id,
    c.IdTipoDocumento,
    td.Descripcion, 
    c.NumeroDocumento,
    c.RazonSocial,
    c.Direccion,
    c.NumeroCelular, 
    c.CorreoElectronico,r.Id as        IdRegimen, 
    c.EsCliente,c.ResponsableIva,
    c.AplicaRetenciones,
    c.IdCiudad,
    c.ActividadEconomica
        FROM $tabla c
        LEFT JOIN bc_tipo_documento td ON td.Id = c.IdTipoDocumento 
        LEFT JOIN bc_regimen r ON r.Id = c.IdRegimen
    {$where_sql}
    ORDER BY c.RazonSocial ASC
    LIMIT %d OFFSET %d
";

$Clientes = $wpdb->get_results( $wpdb->prepare( $select_sql, $prepare_params ) );

$roles = $wpdb->get_results("SELECT * FROM bc_roles");
$tipoIdentificacion = $wpdb->get_results("SELECT * FROM bc_tipo_documento");
$regimenes = $wpdb->get_results("SELECT * FROM bc_regimen");

?>

<!DOCTYPE html>
<div class="toolbar" style="margin-bottom: 20px; display: flex; gap: 10px;">
    <!-- <h1>Clientes</h1> -->
    <form method="get" class="filter-form">
        <input type="hidden" name="view" value="clientes">
        <div class="filter-grid">
            <div class="filter-field full-width input-icon-wrapper">
                <label for="q">Buscar:</label>
                <div class="input-icon-group">
                    <input
                        type="text"
                        id="q"
                        name="q"
                        value=""
                        placeholder="Filtrar por Tipo, Descripción, Usuario o Correo">
                    <button type="submit" class="icon-btn" title="Buscar">🔍</button>
                </div>
            </div>
        </div>
    </form>
    <a href="?view=nuevo_cliente" class="btn btn-icon">
        <svg class="w-[18px] h-[18px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5" />
        </svg>
        Nuevo Registro
    </a>
    <!-- <button type="button" class="btn" onclick="editarSeleccionado()">✏️ Editar</button>
    <button type="button" class="btn" onclick="exportarCSV()">📁 Exportar CSV</button>-->
</div>

<?php if (empty($Clientes)): ?>
    <p>No hay clientes registrados.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Tipo de Documento</th>
                <th>Documento</th>
                <th>Razón Social</th>
                <th>Dirección</th>
                <th>Teléfono</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($Clientes as $b): ?>
                <tr>
                    <td><?= esc_html($b->Descripcion) ?></td>
                    <td><?= esc_html($b->NumeroDocumento) ?></td>
                    <td><?= esc_html($b->RazonSocial ?: 'No Asignado') ?></td>
                    <td><?= esc_html($b->Direccion) ?></td>
                    <td><?= esc_html($b->NumeroCelular) ?></td>
                    <td>

                        <a href="javascript:void(0);" class="detalle-cliente" data-user="<?= esc_attr($b->Id); ?>" style="color: #2980b9; text-decoration: none">

                            <svg class="w-[18px] h-[18px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd" />
                            </svg>
                        </a>
                        <a href="javascript:void(0);" class="modificar-cliente" data-user="<?= esc_attr($b->Id); ?>" style="color: #d7ab00; text-decoration: none">

                            <svg class="w-[18px] h-[18px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M14 4.182A4.136 4.136 0 0 1 16.9 3c1.087 0 2.13.425 2.899 1.182A4.01 4.01 0 0 1 21 7.037c0 1.068-.43 2.092-1.194 2.849L18.5 11.214l-5.8-5.71 1.287-1.31.012-.012Zm-2.717 2.763L6.186 12.13l2.175 2.141 5.063-5.218-2.141-2.108Zm-6.25 6.886-1.98 5.849a.992.992 0 0 0 .245 1.026 1.03 1.03 0 0 0 1.043.242L10.282 19l-5.25-5.168Zm6.954 4.01 5.096-5.186-2.218-2.183-5.063 5.218 2.185 2.15Z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Navegación de páginas -->
    <div style="margin-top: 20px;">
        <?php
        // Botón Anterior
        if ($pagina_actual > 1) {
            $params['pg'] = $pagina_actual - 1;
            echo '<a href="?' . http_build_query($params) . '" class="btn">⬅️ Anterior</a>';
        }

        echo " Página $pagina_actual de $total_paginas ";

        // Botón Siguiente
        if ($pagina_actual < $total_paginas) {
            $params['pg'] = $pagina_actual + 1;
            echo '<a href="?' . http_build_query($params) . '" class="btn">Siguiente ➡️</a>';
        }
        ?>
    </div>
<?php endif; ?>

<?php

?>


<!-- POPUP DETALLE / EDITAR CLIENTE -->
<input type="checkbox" id="popup-toggle">
<div class="overlay">
    <div class="popup">
        <h3 id="popup-title">Detalle Cliente</h3>

        <form id="cliente-form" class="form-grid">
            <input type="hidden" id="cliente-id" name="id" />
            <div class="form-group">
                <label>Tipo Documento</label>
                <select id="IdTipoDocumento" name="IdTipoDocumento" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($tipoIdentificacion as $tipo): ?>
                        <option value="<?= esc_attr($tipo->Id) ?>">
                            <?= esc_html($tipo->Descripcion) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
             <div class="form-group">
                <label>Regimen</label>
                <select id="IdRegimen" name="IdRegimen" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($regimenes as $regimen): ?>
                        <option value="<?= esc_attr($regimen->Id) ?>">
                            <?= esc_html($regimen->Descripcion) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <label>Documento</label>
                <input type="text" id="cliente-doc" name="documento" />
            </div>
            <div class="form-row">
                <label>Razón Social</label>
                <input type="text" id="cliente-razon" name="razon_social" />
            </div>
            <div class="form-row">
                <label>Dirección</label>
                <input type="text" id="cliente-dir" name="direccion" />
            </div>
            <div class="form-row">
                <label>Teléfono</label>
                <input type="text" id="cliente-cel" name="celular" />
            </div>
            <div class="form-row">
                <label>Correo Electrónico</label>
                <input type="email" id="cliente-correo" name="correo" />
            </div>
            <div class="form-row">
                <label>Actividad Economica</label>
                <input type="text" id="cliente-ActividadEconomica" name="ActividadEconomica" />
            </div>
            <div class="form-row">
                <label>Responsable Iva</label>
                <input type="checkbox" id="cliente-ResponsableIva" name="ResponsableIva" />
            </div>
            <div class="form-row">
                <label>Aplica Retenciones</label>
                <input type="checkbox" id="cliente-AplicaRetenciones" name="AplicaRetenciones" />
            </div>

        </form>
        <div style="margin-top: 10px;">
            <label for="popup-toggle" class="btn">Cerrar</label>
            <button type="button" id="btn-guardar" class="btn" style="display: none;">Guardar</button>
        </div>
    </div>
    <div id="loader-overlay">
        <div class="spinner"></div>
    </div>
</div>


<script>
    //seccion para detalle - editar cliente
    const clientes = <?= json_encode($Clientes) ?>;
    const popupToggle = document.querySelector('#popup-toggle');
    const popupTitle = document.querySelector('#popup-title');
    const btnGuardar = document.querySelector('#btn-guardar');
    const inputs = document.querySelectorAll('#cliente-form input');

    function cargarDatosCliente(id, modo) {
        const cliente = clientes.find(c => c.Id == id);
        if (!cliente) {
            alert('Cliente no encontrado en esta página.');
            return;
        }

        // Rellenar formulario
        document.getElementById('cliente-id').value = cliente.Id;
        document.getElementById('IdTipoDocumento').value = cliente.IdTipoDocumento;
        document.getElementById('IdRegimen').value = cliente.IdRegimen;
        document.getElementById('cliente-doc').value = cliente.NumeroDocumento;
        document.getElementById('cliente-razon').value = cliente.RazonSocial;
        document.getElementById('cliente-dir').value = cliente.Direccion;
        document.getElementById('cliente-cel').value = cliente.NumeroCelular;
        document.getElementById('cliente-correo').value = cliente.CorreoElectronico;
        document.getElementById('cliente-ActividadEconomica').value = cliente.ActividadEconomica;
        document.getElementById('cliente-ResponsableIva').checked = cliente.ResponsableIva == 1;
        document.getElementById('cliente-AplicaRetenciones').checked = cliente.AplicaRetenciones == 1;

        const esEditable = modo === 'editar';
        document.getElementById('cliente-doc').readOnly = !esEditable;
        document.getElementById('IdTipoDocumento').disabled = !esEditable;
        document.getElementById('IdRegimen').disabled = !esEditable;
        document.getElementById('cliente-razon').readOnly = !esEditable;
        document.getElementById('cliente-dir').readOnly = !esEditable;
        document.getElementById('cliente-cel').readOnly = !esEditable;
        document.getElementById('cliente-correo').readOnly = !esEditable;
        document.getElementById('cliente-ActividadEconomica').readOnly = !esEditable;
        document.getElementById('cliente-ResponsableIva').disabled = !esEditable;
        document.getElementById('cliente-AplicaRetenciones').disabled = !esEditable;

        // Cambiar título y botón
        popupTitle.textContent = esEditable ? 'Editar Cliente' : 'Detalle Cliente';
        btnGuardar.style.display = esEditable ? 'inline-block' : 'none';
        if (modo === 'detalle') {
            inputs.forEach(input => input.setAttribute('readonly', 'readonly'));
        } else {
            inputs.forEach(input => input.removeAttribute('readonly'));
        }

        popupToggle.checked = true;
    }

    document.querySelectorAll('.detalle-cliente').forEach(btn => {
        btn.addEventListener('click', () => {
            cargarDatosCliente(btn.dataset.user, 'detalle');
        });
    });

    document.querySelectorAll('.modificar-cliente').forEach(btn => {
        btn.addEventListener('click', () => {
            cargarDatosCliente(btn.dataset.user, 'editar');
        });
    });

    document.getElementById('btn-guardar').addEventListener('click', () => {
        const formData = new FormData(document.getElementById('cliente-form'));
        formData.append('action', 'actualizar_cliente');

        showLoader();
        fetch('clientes.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    alert('Cliente actualizado.');
                    location.reload();
                } else {
                    alert('Error: ' + res.data);
                }
            }).finally(hideLoader);
    });

    function showLoader() {
        document.getElementById('loader-overlay').style.display = 'flex';
    }

    function hideLoader() {
        document.getElementById('loader-overlay').style.display = 'none';
    }
</script>