<?php
//define('WP_USE_THEMES', false);
require_once('../../wp-load.php');

global $wpdb;
// --- MANEJO AJAX INTERNO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'actualizar_transporte') {
    $idEntrada = intval($_POST['id']);
    $tabla = 'bc_entrada_bitacora_transporte';

    $data = [
        'Descripcion'   => sanitize_text_field($_POST['descripcion']),
        'Manifiesto'   => sanitize_text_field($_POST['manifiestoEntrada']),
        'IdEntradaBitacora'       => sanitize_text_field($_POST['idEntradaBitacora']),
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

    $result = $wpdb->update($tabla, $data, ['Id' => $idEntrada]);

    // Guardar log en bc_logs
    $log_data = [
        'Objeto'        => wp_json_encode($data),
        'Tabla'         => $tabla,
        'TipoDeCambio'    => 'Actualizar',
        'IdUser'        => get_current_user_id(),
        'FechaCreacion' => current_time('mysql'),
    ];
    $wpdb->insert('bc_logs', $log_data);

    header('Content-Type: application/json');
    if ($result !== false) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'data' => 'No se pudo actualizar.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'actualizar_giros') {
    $idEntrada = intval($_POST['id']);
    $tabla = 'bc_entrada_bitacora_giro';

    $data = [
        'Descripcion'   => sanitize_text_field($_POST['descripcion']),
        'ComprobanteSiigo'   => sanitize_text_field($_POST['ComprobanteSiigo']),
        'IdEntradaBitacora'       => sanitize_text_field($_POST['idEntradaBitacora']),
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

    $result = $wpdb->update($tabla, $data, ['Id' => $idEntrada]);

    // Guardar log en bc_logs
    $log_data = [
        'Objeto'        => wp_json_encode($data),
        'Tabla'         => $tabla,
        'TipoDeCambio'    => 'Actualizar',
        'IdUser'        => get_current_user_id(),
        'FechaCreacion' => current_time('mysql'),
    ];
    $wpdb->insert('bc_logs', $log_data);

    header('Content-Type: application/json');
    if ($result !== false) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'data' => 'No se pudo actualizar.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'actualizar_contabilidad') {
    $idEntrada = intval($_POST['id']);
    $tabla = 'bc_entrada_bitacora_contabilidad';

    $data = [
        'Descripcion'   => sanitize_text_field($_POST['descripcion']),
        'NombreClienteProveedor'   => sanitize_text_field($_POST['NombreClienteProveedor']),
        'IdEntradaBitacora'       => sanitize_text_field($_POST['idEntradaBitacora']),
        'FechaDocumento'       => sanitize_text_field($_POST['FechaDocumento']),
        'IdTipoDocumento' => sanitize_text_field($_POST['IdTipoDocumento']),
        'NumeroDocumento' => sanitize_text_field($_POST['NumeroDocumento']),
        'IdTipoDocumentoContabilidad' => sanitize_text_field($_POST['IdTipoDocumentoContabilidad'])
    ];

    $result = $wpdb->update($tabla, $data, ['Id' => $idEntrada]);

    // Guardar log en bc_logs
    $log_data = [
        'Objeto'        => wp_json_encode($data),
        'Tabla'         => $tabla,
        'TipoDeCambio'    => 'Actualizar',
        'IdUser'        => get_current_user_id(),
        'FechaCreacion' => current_time('mysql'),
    ];
    $wpdb->insert('bc_logs', $log_data);

    header('Content-Type: application/json');
    if ($result !== false) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'data' => 'No se pudo actualizar.']);
    }
    exit;
}

if (!isset($id)) {
    echo '<script>console.warn("ID no definido en PHP.");</script>';
    return;
}
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/wp-content/bitacoras/assets/js/common-loader.js"></script>
<script>
    document.addEventListener('click', function(e) {
        var detalle = e.target.closest('.detalle-entrada');
        var editar = e.target.closest('.modificar-entrada');
        var documentos = e.target.closest('.documentos-entrada');
        if (detalle) {
            showLoader();
            var entradaRaw = detalle.getAttribute('data-entrada');
            var entrada;
            try {
                entrada = JSON.parse(entradaRaw.replaceAll("'", '"'));
            } catch (err) {
                console.error("Error al parsear entrada:", err);
                hideLoader();
                return;
            }

            limpiarPopup();
            document.getElementById('modal-entrada').style.display = 'flex';

            var modo = 'detalle';

            cargarFormularioPorTipo(entrada.TECodigo)
                .then(() => inicializarFormulario(entrada, modo))
                .catch(err => {
                    console.error(err);
                    hideLoader();
                });
        } else if (editar) {
            showLoader();
            var entradaRaw = editar.getAttribute('data-entrada');
            var entrada;
            try {
                entrada = JSON.parse(entradaRaw.replaceAll("'", '"'));
            } catch (err) {
                console.error("Error al parsear entrada:", err);
                hideLoader();
                return;
            }

            limpiarPopup();
            document.getElementById('modal-entrada').style.display = 'flex';

            var modo = 'editar';

            cargarFormularioPorTipo(entrada.TECodigo)
                .then(() => {
                    return inicializarFormulario(entrada, modo);
                })
                .catch(err => {
                    console.error('Error al inicializar formulario:', err);
                    hideLoader();
                });
        } else if (documentos) {
            var entradaRaw = documentos.getAttribute('data-entrada');
            var entrada;
            try {
                entrada = JSON.parse(entradaRaw.replaceAll("'", '"'));
            } catch (err) {
                console.error("Error al parsear entrada:", err);
                return;
            }
            window.location.href = `/wp-content/gestionDocumental/index.php?id_vista=${entrada.Id}&tipo_vista=4`;
        }
    });

    function cargarDatosTransporte(data, modo) {
        showLoader();
        try {
            if (!data) {
                throw new Error('Datos de entrada no encontrados');
            }

            var form = document.getElementById('entrada-form');
            if (!form) {
                form = document.getElementById('form-modal-entrada');
                if (!form) {
                    throw new Error('Formulario no encontrado');
                }
            }
            console.log('Modo recibido en transporte:', modo);

            var esEditable = modo === 'editar';
            var popupTitle = document.getElementById('modal-popup-title') || document.getElementById('popup-title');
            var inputs = form.querySelectorAll('input, select, textarea');
            
            // Configurar el botón de guardar
            var btnGuardar = document.getElementById('guardar-entrada');
            if (!btnGuardar) {
                btnGuardar = document.createElement('button');
                btnGuardar.type = 'button';
                btnGuardar.id = 'guardar-entrada';
                btnGuardar.className = 'button button-primary';
                btnGuardar.textContent = 'Guardar';
                form.appendChild(btnGuardar);
            }

            // Limpiar listeners previos
            var newBtn = btnGuardar.cloneNode(true);
            btnGuardar.parentNode.replaceChild(newBtn, btnGuardar);
            btnGuardar = newBtn;

            // Configurar visibilidad
            btnGuardar.style.display = esEditable ? 'inline-block' : 'none';
            
            // Establecer los valores del formulario
            document.getElementById('IdEntradaBitacora').value = data.IdEntradaBitacora || '';
            document.getElementById('Id').value = data.Id || '';
            document.getElementById('Descripcion').value = data.Descripcion || '';
            document.getElementById('ManifiestoEntrada').value = data.ManifiestoEntrada || '';
            document.getElementById('CiudadDestino').value = data.CiudadDestino || '';
            document.getElementById('Documentacion').value = data.Documentacion || '';
            document.getElementById('CobroCliente').value = data.CobroCliente || '';
            document.getElementById('TamanoContenedor').value = data.TamanoContenedor || '';
            document.getElementById('NumeroContenedor').value = data.NumeroContenedor || '';
            document.getElementById('Conductor').value = data.Conductor || '';
            document.getElementById('Placa').value = data.Placa || '';
            document.getElementById('Remesa').value = data.Remesa || '';
            document.getElementById('FechaElaboracion').value = data.FechaElaboracion || '';
            document.getElementById('FechaSalidaPuerto').value = data.FechaSalidaPuerto || '';
            document.getElementById('FechaEntregaUnidadVacia').value = data.FechaEntregaUnidadVacia || '';

            // Actualizar estado de los campos
            inputs.forEach(function(input) {
                input.disabled = !esEditable;
            });

            // Actualizar título del popup
            if (popupTitle) {
                popupTitle.textContent = esEditable ? 'Editar Transporte' : 'Detalle Transporte';
            }

            // Configurar el evento del botón guardar si estamos en modo edición
            if (esEditable && btnGuardar) {
                btnGuardar.onclick = function() {
                    guardarTransporte(data.Id);
                };
            }

            hideLoader();
        } catch (error) {
            console.error('Error en cargarDatosTransporte:', error);
            Swal.fire({
                title: 'Error',
                text: error.message || 'Error al cargar los datos del transporte',
                icon: 'error'
            });
            hideLoader();
        }
    }

    function cargarDatosGiros(data, modo) {
        showLoader();
        try {
            if (!data) {
                throw new Error('Datos de entrada no encontrados');
            }

            var form = document.getElementById('entrada-form');
            if (!form) {
                form = document.getElementById('form-modal-entrada');
                if (!form) {
                    throw new Error('Formulario no encontrado');
                }
            }
            console.log('Modo recibido en giros:', modo);

            var esEditable = modo === 'editar';
            var popupTitle = document.getElementById('modal-popup-title') || document.getElementById('popup-title');
            var inputs = form.querySelectorAll('input, select, textarea');
            
            // Configurar el botón de guardar
            var btnGuardar = document.getElementById('guardar-entrada');
            if (!btnGuardar) {
                btnGuardar = document.createElement('button');
                btnGuardar.type = 'button';
                btnGuardar.id = 'guardar-entrada';
                btnGuardar.className = 'button button-primary';
                btnGuardar.textContent = 'Guardar';
                form.appendChild(btnGuardar);
            }

            // Limpiar listeners previos
            var newBtn = btnGuardar.cloneNode(true);
            btnGuardar.parentNode.replaceChild(newBtn, btnGuardar);
            btnGuardar = newBtn;

            // Configurar visibilidad
            btnGuardar.style.display = esEditable ? 'inline-block' : 'none';

            // Establecer los valores del formulario
            document.getElementById('IdEntradaBitacora').value = data.IdEntradaBitacora || '';
            document.getElementById('Id').value = data.Id || '';
            document.getElementById('Descripcion').value = data.Descripcion || '';
            document.getElementById('ComprobanteSiigo').value = data.ComprobanteSiigo || '';
            document.getElementById('FechaElaboracion').value = data.FechaElaboracion || '';
            document.getElementById('NombreTercero').value = data.NombreTercero || '';
            document.getElementById('DescripcionMovimiento').value = data.DescripcionMovimiento || '';
            document.getElementById('Debito').value = data.Debito || '';
            document.getElementById('DOCruzado').value = data.DOCruzado || '';
            document.getElementById('Estado').value = data.Estado || '';
            document.getElementById('NumeroDeclaracion').value = data.NumeroDeclaracion || '';
            document.getElementById('USDFOB').value = data.USDFOB || '';
            document.getElementById('USDDeclaradoConFlete').value = data.USDDeclaradoConFlete || '';
            document.getElementById('USDReal').value = data.USDReal || '';
            document.getElementById('FechaMovimiento').value = data.FechaMovimiento || '';
            document.getElementById('Proveedor').value = data.Proveedor || '';

            // Actualizar estado de los campos
            inputs.forEach(function(input) {
                input.disabled = !esEditable;
            });

            // Actualizar título del popup
            if (popupTitle) {
                popupTitle.textContent = esEditable ? 'Editar Giro' : 'Detalle Giro';
            }

            // Configurar el evento del botón guardar si estamos en modo edición
            if (esEditable && btnGuardar) {
                btnGuardar.onclick = function() {
                    guardarGiro(data.Id);
                };
            }

            hideLoader();
        } catch (error) {
            console.error('Error en cargarDatosGiros:', error);
            Swal.fire({
                title: 'Error',
                text: error.message || 'Error al cargar los datos del giro',
                icon: 'error'
            });
            hideLoader();
        }
    }

    function cargarDatosContabilidad(data, modo) {
        showLoader();
        try {
            if (!data) {
                throw new Error('Datos de entrada no encontrados');
            }

            var form = document.getElementById('entrada-form');
            if (!form) {
                form = document.getElementById('form-modal-entrada');
                if (!form) {
                    throw new Error('Formulario no encontrado');
                }
            }
            console.log('Modo recibido en contabilidad:', modo);

            var esEditable = modo === 'editar';
            var popupTitle = document.getElementById('modal-popup-title') || document.getElementById('popup-title');
            var inputs = form.querySelectorAll('input, select, textarea');
            
            // Configurar el botón de guardar
            var btnGuardar = document.getElementById('guardar-entrada');
            if (!btnGuardar) {
                btnGuardar = document.createElement('button');
                btnGuardar.type = 'button';
                btnGuardar.id = 'guardar-entrada';
                btnGuardar.className = 'button button-primary';
                btnGuardar.textContent = 'Guardar';
                form.appendChild(btnGuardar);
            }

            // Limpiar listeners previos
            var newBtn = btnGuardar.cloneNode(true);
            btnGuardar.parentNode.replaceChild(newBtn, btnGuardar);
            btnGuardar = newBtn;

            // Configurar visibilidad
            btnGuardar.style.display = esEditable ? 'inline-block' : 'none';

            // Establecer los valores del formulario
            document.getElementById('IdEntradaBitacora').value = data.IdEntradaBitacora || '';
            document.getElementById('Id').value = data.Id || '';
            document.getElementById('Descripcion').value = data.Descripcion || '';
            document.getElementById('NombreClienteProveedor').value = data.NombreClienteProveedor || '';
            document.getElementById('FechaDocumento').value = data.FechaDocumento || '';
            document.getElementById('IdTipoDocumento').value = data.IdTipoDocumento || '';
            document.getElementById('NumeroDocumento').value = data.NumeroDocumento || '';
            document.getElementById('IdTipoDocumentoContabilidad').value = data.IdTipoDocumentoContabilidad || '';

            // Actualizar estado de los campos
            inputs.forEach(function(input) {
                input.disabled = !esEditable;
            });

            // Actualizar título del popup
            if (popupTitle) {
                popupTitle.textContent = esEditable ? 'Editar Contabilidad' : 'Detalle Contabilidad';
            }

            // Configurar el evento del botón guardar si estamos en modo edición
            if (esEditable && btnGuardar) {
                btnGuardar.onclick = function() {
                    guardarContabilidad(data.Id);
                };
            }

            hideLoader();
        } catch (error) {
            console.error('Error en cargarDatosContabilidad:', error);
            Swal.fire({
                title: 'Error',
                text: error.message || 'Error al cargar los datos de contabilidad',
                icon: 'error'
            });
            hideLoader();
        }
    }

    function limpiarPopup() {
        var modalBody = document.getElementById('modal-popup-content');
        if (modalBody) modalBody.innerHTML = '';
    }

    function showLoader() {
        document.getElementById('loader-overlay').style.display = 'flex';
    }

    function hideLoader() {
        document.getElementById('loader-overlay').style.display = 'none';
    }
</script>

</html>