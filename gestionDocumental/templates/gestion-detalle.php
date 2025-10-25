<?php
if (!defined('ABSPATH')) {
    exit;
}

$total_pages = $per_page > 0 ? (int) ceil($total_docs / $per_page) : 1;
$current_page = max(1, (int) $page);
$base_args = ['view' => 'gestion_detalle', 'id' => $gestionId];
if (!empty($q)) {
    $base_args['q'] = $q;
}
?>
<div class="wrap gestion-documental-detalle">
    <h1 class="wp-heading-inline">Gestión documental: <?php echo esc_html($nombreImportador); ?></h1>
    <a class="page-title-action" href="<?php echo esc_url(add_query_arg(['view' => 'gestion_documental'], remove_query_arg(['view', 'id', 'paged']))); ?>">Volver a la lista</a>

    <form method="get" class="search-form" style="margin-top:1em;">
        <input type="hidden" name="view" value="gestion_detalle" />
        <input type="hidden" name="id" value="<?php echo esc_attr($gestionId); ?>" />
        <label class="screen-reader-text" for="document-search">Buscar documentos</label>
        <input type="search" id="document-search" name="q" value="<?php echo esc_attr($q); ?>" placeholder="Buscar por nombre o tipo" />
        <button type="submit" class="button">Buscar</button>
        <?php if (!empty($q)) : ?>
            <a class="button button-secondary" href="<?php echo esc_url(add_query_arg(['view' => 'gestion_detalle', 'id' => $gestionId], remove_query_arg(['q', 'paged']))); ?>">Limpiar</a>
        <?php endif; ?>
    </form>

    <?php if (!empty($mensaje)) : ?>
        <div class="notice notice-info" style="margin-top:1em;">
            <p><?php echo wp_kses_post($mensaje); ?></p>
        </div>
    <?php endif; ?>

    <div class="gestion-documental-lista" style="margin-top:1em;">
        <?php if (!empty($documentosAgrupados)) : ?>
            <?php foreach ($documentosAgrupados as $tipo => $porFecha) : ?>
                <h2><?php echo esc_html($tipo); ?></h2>
                <?php foreach ($porFecha as $fecha => $documentos) : ?>
                    <h3><?php echo esc_html(mysql2date('d \d\e F Y', $fecha)); ?></h3>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Cliente / Proveedor</th>
                                <th>Tipo contable</th>
                                <th>Fecha documento</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documentos as $documento) : ?>
                                <tr>
                                    <td><?php echo esc_html($documento->NombreArchivo); ?></td>
                                    <td><?php echo esc_html($documento->NombreClienteProveedor ?? ''); ?></td>
                                    <td><?php echo esc_html($documento->TipoDocContabilidad ?? ''); ?></td>
                                    <td><?php echo esc_html(!empty($documento->FechaDocumento) ? mysql2date('d/m/Y', $documento->FechaDocumento) : ''); ?></td>
                                    <td>
                                        <?php if (!empty($documento->RutaArchivo)) : ?>
                                            <a class="button button-small" target="_blank" rel="noopener" href="<?php echo esc_url($documento->RutaArchivo); ?>">Abrir</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php else : ?>
            <p>No hay documentos asociados a esta gestión.</p>
        <?php endif; ?>
    </div>

    <?php if ($total_pages > 1) : ?>
        <div class="tablenav" style="margin-top:1em;">
            <div class="tablenav-pages">
                <?php
                echo wp_kses_post(paginate_links([
                    'total'   => $total_pages,
                    'current' => $current_page,
                    'base'    => add_query_arg(array_merge($base_args, ['paged' => '%#%'])),
                    'format'  => '',
                ]));
                ?>
            </div>
        </div>
    <?php endif; ?>

    <hr />

    <h2>Registrar nuevo documento</h2>
    <form method="post" enctype="multipart/form-data" style="margin-top:1em;">
        <?php wp_nonce_field('crear_documento_gestion', 'documento_gestion_nonce'); ?>
        <input type="hidden" name="gestion_id" value="<?php echo esc_attr($gestionId); ?>" />

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="tipo_documento">Tipo de documento</label></th>
                    <td>
                        <select name="tipo_documento" id="tipo_documento" required>
                            <option value="">Seleccione una opción</option>
                            <?php foreach ($tiposDocumento as $tipo) : ?>
                                <option value="<?php echo esc_attr($tipo->Id); ?>" <?php selected(isset($_POST['tipo_documento']) && (int) $_POST['tipo_documento'] === (int) $tipo->Id); ?>>
                                    <?php echo esc_html($tipo->Nombre); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="nombre">Nombre</label></th>
                    <td>
                        <input type="text" name="nombre" id="nombre" value="<?php echo isset($_POST['nombre']) ? esc_attr(wp_unslash($_POST['nombre'])) : ''; ?>" class="regular-text" required />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="archivo">Archivo</label></th>
                    <td>
                        <input type="file" name="archivo" id="archivo" accept="application/pdf,image/*" />
                        <p class="description">Opcional. Si se adjunta, el archivo se guardará automáticamente en Google Drive.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="cliente_proveedor">Cliente / Proveedor</label></th>
                    <td>
                        <select name="cliente_proveedor" id="cliente_proveedor">
                            <option value="">Sin asociar</option>
                            <?php foreach ($tiposDocCliente as $cliente) : ?>
                                <option value="<?php echo esc_attr($cliente->ID); ?>" <?php selected(isset($_POST['cliente_proveedor']) && (int) $_POST['cliente_proveedor'] === (int) $cliente->ID); ?>>
                                    <?php echo esc_html($cliente->RazonSocial . ' - ' . $cliente->NumeroDocumento); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="tipo_doc_conta">Tipo documento contable</label></th>
                    <td>
                        <select name="tipo_doc_conta" id="tipo_doc_conta">
                            <option value="">Sin asociar</option>
                            <?php foreach ($tiposDocContabilidad as $tipoConta) : ?>
                                <option value="<?php echo esc_attr($tipoConta->ID); ?>" <?php selected(isset($_POST['tipo_doc_conta']) && (int) $_POST['tipo_doc_conta'] === (int) $tipoConta->ID); ?>>
                                    <?php echo esc_html($tipoConta->Descripcion); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="fecha_documento">Fecha del documento</label></th>
                    <td>
                        <input type="date" name="fecha_documento" id="fecha_documento" value="<?php echo isset($_POST['fecha_documento']) ? esc_attr(wp_unslash($_POST['fecha_documento'])) : ''; ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="descripcion">Descripción</label></th>
                    <td>
                        <textarea name="descripcion" id="descripcion" rows="3" class="large-text"><?php echo isset($_POST['descripcion']) ? esc_textarea(wp_unslash($_POST['descripcion'])) : ''; ?></textarea>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary">Guardar documento</button>
        </p>
    </form>
</div>
