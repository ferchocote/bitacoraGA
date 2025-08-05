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
        'TamanoContenedor' => sanitize_email($_POST['tamanoContenedor']),
        'NumeroContenedor' => sanitize_text_field($_POST['numeroContenedor']),
        'Conductor' => sanitize_text_field($_POST['conductor']),
        'Placa' => sanitize_text_field($_POST['placa']),
        'Remesa' => sanitize_text_field($_POST['remesa']),
        'FechaElaboracion' => sanitize_text_field($_POST['fechaElaboracion']),
        'FechaSalidaPuerto' => sanitize_text_field($_POST['fechaSalidaPuerto']),
        'FechaEntregaUnidadVacia' => sanitize_text_field($_POST['fechaEntregaUnidadVacia'])

    ];
    //echo "<script>console.log(" . json_encode($_POST) . ");</script>";
    // echo "<script>console.log(" . json_encode($tabla) . ");</script>";
    // echo "<script>console.log(" . json_encode($id) . ");</script>";



    $result = $wpdb->update($tabla, $data, ['Id' => $idEntrada]);

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
        'Estado' => sanitize_text_field($_POST['Estado']),
        'DO' => sanitize_text_field($_POST['DO']),
        'NumeroDeclaracion' => sanitize_text_field($_POST['NumeroDeclaracion']),
        'USDFOB' => sanitize_text_field($_POST['USDFOB']),
        'USDDeclaradoConFlete' => sanitize_text_field($_POST['USDDeclaradoConFlete']),
        'USDReal' => sanitize_text_field($_POST['USDReal']),
        'FechaMovimiento' => sanitize_text_field($_POST['FechaMovimiento']),
        'Proveedor' => sanitize_text_field($_POST['Proveedor'])

    ];
    //echo "<script>console.log(" . json_encode($_POST) . ");</script>";
    // echo "<script>console.log(" . json_encode($tabla) . ");</script>";
    // echo "<script>console.log(" . json_encode($id) . ");</script>";



    $result = $wpdb->update($tabla, $data, ['Id' => $idEntrada]);

    header('Content-Type: application/json');
    if ($result !== false) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'data' => 'No se pudo actualizar.']);
    }
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'actualizar_contabilidad') {
    //$id = intval($_POST['IdProceso']);
    $idEntrada = intval($_POST['id']);
    $tabla = 'bc_entrada_bitacora_contabilidad';


    $data = [
        'Descripcion'   => sanitize_text_field($_POST['descripcion']),
        'NombreClienteProveedor'   => sanitize_text_field($_POST['NombreClienteProveedor']),
        'IdEntradaBitacora'       => sanitize_text_field($_POST['idEntradaBitacora']),
        'FechaDocumento'       => sanitize_text_field($_POST['FechaDocumento']),
        'FechaIngresoSistema'         => sanitize_text_field($_POST['FechaIngresoSistema']),
        'FechaVencimiento'     => sanitize_text_field($_POST['FechaVencimiento']),
        'IdTipoDocumento' => sanitize_text_field($_POST['IdTipoDocumento']),
        'IdTipoDocumentoContabilidad' => sanitize_text_field($_POST['IdTipoDocumentoContabilidad'])

    ];

    //echo "<script>console.log(" . json_encode($_POST) . ");</script>";
    // echo "<script>console.log(" . json_encode($tabla) . ");</script>";
    // echo "<script>console.log(" . json_encode($id) . ");</script>";



    $result = $wpdb->update($tabla, $data, ['Id' => $idEntrada]);

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

<!-- POPUP ENTRADA / EDITAR ENTRADA -->
<input type="checkbox" id="popup-toggle">
<div class="overlay">
    <div id="formulario-popup-container" class="popup"></div>
    <div id="loader-overlay">
        <div class="spinner"></div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('click', function(e) {
        const detalle = e.target.closest('.detalle-entrada');
        const editar = e.target.closest('.modificar-entrada');
        const documentos = e.target.closest('.documentos-entrada');
        if (detalle) {
            const entradaRaw = detalle.getAttribute('data-entrada');
            let entrada;
            try {
                entrada = JSON.parse(entradaRaw.replaceAll("'", '"')); // Maneja si el HTML escapó comillas
            } catch (err) {
                console.error("Error al parsear entrada:", err);
                return;
            }


            // Mostrar el popup
            document.getElementById('popup-toggle').checked = true;

            const modo = detalle ? 'detalle' : 'editar';
            document.getElementById('popup-title').textContent = modo === 'detalle' ? 'Detalle Transporte' : 'Editar Transporte';


            // Mostrar u ocultar botón guardar
            document.getElementById('btn-guardar').style.display = modo === 'editar' ? 'inline-block' : 'none';

            // Esperar que el HTML cargue y luego enviar item
            setTimeout(() => {
                if (typeof window.inicializarFormulario === 'function') {
                    window.inicializarFormulario(entrada, modo);
                }
            }, 100);


        } else if (editar) {
            const entradaRaw = editar.getAttribute('data-entrada');
            let entrada;
            try {
                entrada = JSON.parse(entradaRaw.replaceAll("'", '"')); // Maneja si el HTML escapó comillas
            } catch (err) {
                console.error("Error al parsear entrada:", err);
                return;
            }


            // Mostrar el popup
            document.getElementById('popup-toggle').checked = true;

            const modo = detalle ? 'detalle' : 'editar';
            document.getElementById('popup-title').textContent = modo === 'detalle' ? 'Detalle Transporte' : 'Editar Transporte';


            // Mostrar u ocultar botón guardar
            document.getElementById('btn-guardar').style.display = modo === 'editar' ? 'inline-block' : 'none';

            // Esperar que el HTML cargue y luego enviar item
            setTimeout(() => {
                if (typeof window.inicializarFormulario === 'function') {
                    window.inicializarFormulario(entrada, modo);
                }
            }, 100);
        } else if (documentos) {
            const entradaRaw = documentos.getAttribute('data-entrada');
            let entrada;
            try {
                entrada = JSON.parse(entradaRaw.replaceAll("'", '"'));
            } catch (err) {
                console.error("Error al parsear entrada:", err);
                return;
            }
            // Mostrar modal de documentos y cargar archivos asociados

            mostrarModalDocumentos(entrada);
        }
    });




    function inicializarFormulario(data, modo) {
        console.log("Datos recibidos:", data);
        const entradas = data;
        switch (entradas.TECodigo) {
            case 'TRS':
                cargarDatosTransporte(entradas, modo);
                break;
            case 'GRO':
                cargarDatosGiros(entradas, modo);
                break;
            case 'CTB':
                cargarDatosContabilidad(entradas, modo);
                break;
            default:
                console.warn('Tipo de entrada no reconocido:', entradas.TECodigo);
        }
    }

    function cargarEntradas(tipoTab, contenedorId, idProceso = <?= json_encode($id) ?>) {
        const acciones = {
            CTB: 'get_entradas_contabilidad',
            GRO: 'get_entradas_giros',
            TRS: 'get_entradas_transporte'
        };

        const action = acciones[tipoTab];
        if (!action) {
            document.getElementById(contenedorId).innerHTML = '<p>Tipo de pestaña desconocido.</p>';
            return;
        }

        const ajaxUrl = '/wp-content/bitacoras/plugins/cliente/entradas-ajax.php';
        const contenedor = document.getElementById(contenedorId);
        contenedor.innerHTML = '<p>Cargando entradas...</p>';


        fetch(`${ajaxUrl}?action=${action}&id_proceso=${idProceso}`)
            .then(res => res.json())
            .then(data => {
                contenedor.innerHTML = '';

                if (!Array.isArray(data) || data.length === 0) {
                    contenedor.innerHTML = '<p>No hay entradas disponibles.</p>';
                    return;
                }


                cargarFormularioPorTipo(tipoTab);

                const table = document.createElement('table');
                table.innerHTML = `
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Usuario</th>
                        <th>Correo</th>
                        <th>Fecha</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    ${data.map(item => `
                        <tr>
                            <td>${item.TEDescripcion || 'N/A'}</td>
                            <td>${item.Descripcion || 'Sin descripción'}</td>
                            <td>${item.user_nicename || '---'}</td>
                            <td>${item.user_email || '---'}</td>
                            <td>${item.FechaCreacion ? new Date(item.FechaCreacion).toLocaleDateString() : '---'}</td>
                            <td>
                            <a href="javascript:void(0);" class="detalle-entrada"  data-entrada='${JSON.stringify(item).replace(/'/g, "&apos;")}' style="color: #2980b9; text-decoration: none" title="Ver detalle">
                                <svg class="w-[18px] h-[18px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd" />
                                </svg>
                            </a>
                            <?php if ($usuario->rol_codigo === 'ADMIN' || $usuario->rol_codigo === 'GIRO' || $usuario->rol_codigo === 'TRANS' || $usuario->rol_codigo === 'CONT') : ?>
                            <a href="javascript:void(0);" class="modificar-entrada" data-entrada='${JSON.stringify(item).replace(/'/g, "&apos;")}' style="color: #d7ab00; text-decoration: none">
                                <svg class="w-[18px] h-[18px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M14 4.182A4.136 4.136 0 0 1 16.9 3c1.087 0 2.13.425 2.899 1.182A4.01 4.01 0 0 1 21 7.037c0 1.068-.43 2.092-1.194 2.849L18.5 11.214l-5.8-5.71 1.287-1.31.012-.012Zm-2.717 2.763L6.186 12.13l2.175 2.141 5.063-5.218-2.141-2.108Zm-6.25 6.886-1.98 5.849a.992.992 0 0 0 .245 1.026 1.03 1.03 0 0 0 1.043.242L10.282 19l-5.25-5.168Zm6.954 4.01 5.096-5.186-2.218-2.183-5.063 5.218 2.185 2.15Z" clip-rule="evenodd" />
                                </svg>
                            </a>
                            <?php endif; ?>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            `;
                contenedor.appendChild(table);
            })
            .catch(err => {
                console.error("Error al cargar datos:", err);
                contenedor.innerHTML = '<p>Error al cargar datos.</p>';
            });
    }


    function cargarFormularioPorTipo(tipoTab) {
        const rutas = {
            CTB: '/wp-content/bitacoras/entradas/contabilidad.php',
            GRO: '/wp-content/bitacoras/entradas/giros.php',
            TRS: '/wp-content/bitacoras/entradas/transporte.php'
        };

        const ruta = rutas[tipoTab];
        if (!ruta) {
            document.getElementById('formulario-popup-container').innerHTML = '<p>Formulario no disponible.</p>';
            return;
        }

        fetch(ruta)
            .then(res => res.text())
            .then(html => {
                document.getElementById('formulario-popup-container').innerHTML = html;


            })
            .catch(err => {
                console.error('Error cargando formulario:', err);
                document.getElementById('formulario-popup-container').innerHTML = '<p>Error al cargar formulario.</p>';
            });
    }

    function cargarDatosTransporte(data, modo) {

        const popupToggle = document.querySelector('#popup-toggle');
        const popupTitle = document.querySelector('#popup-title');
        const btnGuardar = document.querySelector('#btn-guardar');
        const inputs = document.querySelectorAll('#entrada-form input');

        if (!data) {
            alert('Entrada no encontrado en esta página.');
            return;
        }

        document.getElementById('btn-guardar').addEventListener('click', () => {
            const formData = new FormData(document.getElementById('entrada-form'));
            formData.append('action', 'actualizar_transporte');

            showLoader();
            fetch('bitacora_detalle_filtro.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Actualizado',
                            text: 'La entrada fue actualizada correctamente.',
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

        // Rellenar formulario
        document.getElementById('IdEntradaBitacora').value = data.IdEntradaBitacora;
        document.getElementById('Id').value = data.Id;
        document.getElementById('Descripcion').value = data.Descripcion;
        document.getElementById('Documentacion').value = data.Documentacion;
        document.getElementById('CobroCliente').value = data.CobroCliente;
        document.getElementById('ManifiestoEntrada').value = data.Manifiesto;
        document.getElementById('TamanoContenedor').value = data.TamanoContenedor;
        document.getElementById('NumeroContenedor').value = data.NumeroContenedor;
        document.getElementById('Conductor').value = data.Conductor;
        document.getElementById('Placa').value = data.Placa;
        document.getElementById('Remesa').value = data.Remesa;
        document.getElementById('CiudadDestino').value = data.CiudadDestino;
        document.getElementById('FechaElaboracion').value = data.FechaElaboracion;
        document.getElementById('FechaSalidaPuerto').value = data.FechaSalidaPuerto;
        document.getElementById('FechaEntregaUnidadVacia').value = data.FechaEntregaUnidadVacia;

        const esEditable = modo === 'editar';
        document.getElementById('Descripcion').disabled = !esEditable;
        document.getElementById('Documentacion').disabled = !esEditable;
        document.getElementById('CobroCliente').disabled = !esEditable;
        document.getElementById('ManifiestoEntrada').disabled = !esEditable;
        document.getElementById('TamanoContenedor').disabled = !esEditable;
        document.getElementById('NumeroContenedor').disabled = !esEditable;
        document.getElementById('Conductor').disabled = !esEditable;
        document.getElementById('Placa').disabled = !esEditable;
        document.getElementById('Remesa').disabled = !esEditable;
        document.getElementById('CiudadDestino').disabled = !esEditable;
        document.getElementById('FechaElaboracion').disabled = !esEditable;
        document.getElementById('FechaSalidaPuerto').disabled = !esEditable;
        document.getElementById('FechaEntregaUnidadVacia').disabled = !esEditable;




        popupTitle.textContent = esEditable ? 'Editar Transporte' : 'Detalle Transporte';
        btnGuardar.style.display = esEditable ? 'inline-block' : 'none';
        if (modo === 'detalle') {
            inputs.forEach(input => input.setAttribute('readonly', 'readonly'));
        } else {
            inputs.forEach(input => input.removeAttribute('readonly'));
        }

        popupToggle.checked = true;
    }

    function cargarDatosGiros(data, modo) {

        const popupToggle = document.querySelector('#popup-toggle');
        const popupTitle = document.querySelector('#popup-title');
        const btnGuardar = document.querySelector('#btn-guardar');
        const inputs = document.querySelectorAll('#entrada-form input');

        if (!data) {
            alert('Entrada no encontrado en esta página.');
            return;
        }

        document.getElementById('btn-guardar').addEventListener('click', () => {
            const formData = new FormData(document.getElementById('entrada-form'));
            formData.append('action', 'actualizar_giros');

            showLoader();
            fetch('bitacora_detalle_filtro.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Actualizado',
                            text: 'La entrada fue actualizada correctamente.',
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

        // Rellenar formulario
        document.getElementById('IdEntradaBitacora').value = data.IdEntradaBitacora;
        document.getElementById('Id').value = data.Id;
        document.getElementById('Descripcion').value = data.Descripcion;
        document.getElementById('ComprobanteSiigo').value = data.ComprobanteSiigo;
        document.getElementById('FechaElaboracion').value = data.FechaElaboracion;
        document.getElementById('NombreTercero').value = data.NombreTercero;
        document.getElementById('DescripcionMovimiento').value = data.DescripcionMovimiento;
        document.getElementById('Debito').value = data.Debito;
        document.getElementById('DOCruzado').value = data.DOCruzado;
        document.getElementById('Estado').value = data.Estado;
        document.getElementById('DO').value = data.DO;
        document.getElementById('NumeroDeclaracion').value = data.NumeroDeclaracion;
        document.getElementById('USDFOB').value = data.USDFOB;
        document.getElementById('USDDeclaradoConFlete').value = data.USDDeclaradoConFlete;
        document.getElementById('USDReal').value = data.USDReal;
        document.getElementById('FechaMovimiento').value = data.FechaMovimiento;
        document.getElementById('Proveedor').value = data.Proveedor;

        const esEditable = modo === 'editar';

        document.getElementById('Descripcion').disabled = !esEditable;
        document.getElementById('ComprobanteSiigo').disabled = !esEditable;
        document.getElementById('FechaElaboracion').disabled = !esEditable;
        document.getElementById('NombreTercero').disabled = !esEditable;
        document.getElementById('DescripcionMovimiento').disabled = !esEditable;
        document.getElementById('Debito').disabled = !esEditable;
        document.getElementById('DOCruzado').disabled = !esEditable;
        document.getElementById('Estado').disabled = !esEditable;
        document.getElementById('DO').disabled = !esEditable;
        document.getElementById('NumeroDeclaracion').disabled = !esEditable;
        document.getElementById('USDFOB').disabled = !esEditable;
        document.getElementById('USDDeclaradoConFlete').disabled = !esEditable;
        document.getElementById('USDReal').disabled = !esEditable;
        document.getElementById('FechaMovimiento').disabled = !esEditable;
        document.getElementById('Proveedor').disabled = !esEditable;






        popupTitle.textContent = esEditable ? 'Editar Giro' : 'Detalle Giro';
        btnGuardar.style.display = esEditable ? 'inline-block' : 'none';
        if (modo === 'detalle') {
            inputs.forEach(input => input.setAttribute('readonly', 'readonly'));
        } else {
            inputs.forEach(input => input.removeAttribute('readonly'));
        }

        popupToggle.checked = true;
    }

    function cargarDatosContabilidad(data, modo) {

        const popupToggle = document.querySelector('#popup-toggle');
        const popupTitle = document.querySelector('#popup-title');
        const btnGuardar = document.querySelector('#btn-guardar');
        const inputs = document.querySelectorAll('#entrada-form input');

        if (!data) {
            alert('Entrada no encontrado en esta página.');
            return;
        }

        document.getElementById('btn-guardar').addEventListener('click', () => {
            const formData = new FormData(document.getElementById('entrada-form'));
            formData.append('action', 'actualizar_contabilidad');

            showLoader();
            fetch('bitacora_detalle_filtro.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Actualizado',
                            text: 'La entrada fue actualizada correctamente.',
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




        document.getElementById('IdEntradaBitacora').value = data.IdEntradaBitacora;
        document.getElementById('Id').value = data.Id;
        document.getElementById('Descripcion').value = data.Descripcion;
        document.getElementById('NombreClienteProveedor').value = data.NombreClienteProveedor;
        document.getElementById('FechaDocumento').value = data.FechaDocumento;
        document.getElementById('FechaIngresoSistema').value = data.FechaIngresoSistema;
        document.getElementById('FechaVencimiento').value = data.FechaVencimiento;
        document.getElementById('IdTipoDocumento').value = data.IdTipoDocumento;
        document.getElementById('IdTipoDocumentoContabilidad').value = data.IdTipoDocumentoContabilidad;


        const esEditable = modo === 'editar';
        document.getElementById('Descripcion').disabled = !esEditable;
        document.getElementById('NombreClienteProveedor').disabled = !esEditable;
        document.getElementById('FechaDocumento').disabled = !esEditable;
        document.getElementById('FechaIngresoSistema').disabled = !esEditable;
        document.getElementById('FechaVencimiento').disabled = !esEditable;
        document.getElementById('IdTipoDocumento').disabled = !esEditable;
        document.getElementById('IdTipoDocumentoContabilidad').disabled = !esEditable;





        popupTitle.textContent = esEditable ? 'Editar Contabilidad' : 'Detalle Contabilidad';
        btnGuardar.style.display = esEditable ? 'inline-block' : 'none';
        if (modo === 'detalle') {
            inputs.forEach(input => input.setAttribute('readonly', 'readonly'));
        } else {
            inputs.forEach(input => input.removeAttribute('readonly'));
        }

        popupToggle.checked = true;
    }

    function showLoaderdocumento() { // Mantengo el nombre que ya usabas
        console.log('Mostrando loader de documentos...');
        const loaderOverlayDoc = document.getElementById('loader-overlay-documento'); // Apunta al nuevo ID
        if (loaderOverlayDoc) {
            loaderOverlayDoc.style.display = 'flex';
        } else {
            console.error('Elemento #loader-overlay-documentos no encontrado en el DOM.');
        }
    }

    function hideLoaderDocumento() { // Nueva función para ocultar el loader de documentos
        console.log('Ocultando loader de documentos...');
        const loaderOverlayDoc = document.getElementById('loader-overlay-documento'); // Apunta al nuevo ID
        if (loaderOverlayDoc) {
            loaderOverlayDoc.style.display = 'none';
        }
    }

    function mostrarModalDocumentos(entrada) {
        document.getElementById('modal-documentos').style.display = 'flex';
        document.getElementById('doc-id-entrada').value = entrada.IdEntradaBitacora;
        cargarListaDocumentos(entrada.IdEntradaBitacora);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const btnCerrar = document.getElementById('cerrar-modal-documentos');
        const formSubir = document.getElementById('form-subir-documento');
        if (btnCerrar) {
            btnCerrar.onclick = function() {
                document.getElementById('modal-documentos').style.display = 'none';
            };
        }
        if (formSubir) {
            formSubir.onsubmit = function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                showLoaderdocumento();
                fetch('/wp-content/bitacoras/plugins/cliente/entradas-ajax.php?action=subir_documento', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(resp => {
                        if (resp.success) {
                            
                            cargarListaDocumentos(formData.get('id_entrada'));
                            this.reset();
                        } else {
                            this.reset();
                            document.getElementById('modal-documentos').style.display = 'none';
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: resp.msg || 'Ocurrió un error inesperado.'
                            });
                        }
                    }).finally(hideLoaderDocumento);
            };
        }

    });


    function cargarListaDocumentos(idEntrada) {
        hideLoaderDocumento();
        showLoaderdocumento();
        fetch(`/wp-content/bitacoras/plugins/cliente/entradas-ajax.php?action=listar_documentos&id_entrada=${idEntrada}`)
            .then(res => res.json())
            .then(data => {
                const cont = document.getElementById('lista-documentos');
                if (data.length === 0) {
                    cont.innerHTML = '<em>No hay documentos.</em>';
                } else {
                    cont.innerHTML = `
                    <table class="tabla-documentos">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Fecha Cargue</th>
                                
                            </tr>
                        </thead>
                        <tbody>
                            ${data.map(doc => `
                                <tr>
                                    <td>
                                        <a href="${doc.url}" target="_blank" download>${doc.nombre}</a>
                                    </td>
                                    <td>${doc.fecha || 'N/A'}</td>
                                    
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
                }
            }).finally(hideLoaderDocumento);
    }



    function eliminarDocumento(idDoc, idEntrada) {
        if (!confirm('¿Eliminar este documento?')) return;
        fetch(`/wp-content/bitacoras/plugins/cliente/documentos-ajax.php?action=eliminar&id=${idDoc}`, {
                method: 'POST'
            })
            .then(res => res.json())
            .then(resp => {
                if (resp.success) cargarListaDocumentos(idEntrada);
                else alert('No se pudo eliminar');
            });
    }
</script>

<!-- Modal para Documentos -->
<div id="modal-documentos" class="modal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); z-index: index 1;; align-items:center; justify-content:center;">
    <div style="background:#fff; padding:24px; border-radius:8px; min-width:350px; max-width:90vw; max-height:90vh; overflow:auto; position:relative;">
        <button id="cerrar-modal-documentos" style="position:absolute; top:8px; right:8px;">&times;</button>
        <h3>Documentos de la Entrada</h3>
        <form id="form-subir-documento" >
            <input type="file" name="archivo" required>
            <input type="hidden" name="id_entrada" id="doc-id-entrada">
            <button type="submit">Subir</button>
            <div id="loader-overlay-documento">
                <div class="spinner"></div>
            </div>

        </form>
        <div id="lista-documentos" style="margin-top:16px;"></div>

    </div>

</div>