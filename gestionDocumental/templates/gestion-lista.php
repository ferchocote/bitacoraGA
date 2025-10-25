<?php
if (!defined('ABSPATH')) {
    exit;
}

$total_pages = $per_page > 0 ? (int) ceil($total_gestiones / $per_page) : 1;
$current_page = max(1, (int) $page);
$base_args = ['view' => 'gestion_documental'];
if (!empty($q)) {
    $base_args['q'] = $q;
}
?>
<div class="wrap gestion-documental">
    <h1 class="wp-heading-inline">Gestión documental</h1>
    <a href="<?php echo esc_url(add_query_arg(['view' => 'gestion_documental', 'action' => 'nueva'], remove_query_arg(['action', 'paged']))); ?>" class="page-title-action">Nueva gestión</a>

    <form method="get" class="search-form" style="margin-top: 1em;">
        <input type="hidden" name="view" value="gestion_documental" />
        <label class="screen-reader-text" for="gestion-search">Buscar gestiones</label>
        <input type="search" id="gestion-search" name="q" value="<?php echo esc_attr($q); ?>" placeholder="Buscar por importador o documento" />
        <button type="submit" class="button">Buscar</button>
        <?php if (!empty($q)) : ?>
            <a class="button button-secondary" href="<?php echo esc_url(add_query_arg(['view' => 'gestion_documental'], remove_query_arg(['q', 'paged']))); ?>">Limpiar</a>
        <?php endif; ?>
    </form>

    <?php if (!empty($mensaje)) : ?>
        <div class="notice notice-info" style="margin-top:1em;"><p><?php echo wp_kses_post($mensaje); ?></p></div>
    <?php endif; ?>

    <table class="wp-list-table widefat fixed striped" style="margin-top:1em;">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Importador</th>
                <th scope="col">Número documento</th>
                <th scope="col">Fecha creación</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($importadores)) : ?>
                <?php foreach ($importadores as $gestion) : ?>
                    <tr>
                        <td><?php echo esc_html($gestion->GestionID ?? $gestion->ID); ?></td>
                        <td>
                            <strong><?php echo esc_html($gestion->RazonSocial ?? __('Sin nombre', 'gestion-documental')); ?></strong>
                        </td>
                        <td><?php echo esc_html($gestion->NumeroDocumento ?? ''); ?></td>
                        <td><?php echo esc_html(isset($gestion->FechaCreacion) ? mysql2date('d/m/Y H:i', $gestion->FechaCreacion) : ''); ?></td>
                        <td>
                            <a class="button button-small" href="<?php echo esc_url(add_query_arg(['view' => 'gestion_detalle', 'id' => $gestion->GestionID ?? $gestion->ID])); ?>">
                                Ver detalle
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="5">No se encontraron gestiones documentales.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1) : ?>
        <div class="tablenav">
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
</div>
