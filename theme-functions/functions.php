<?php
/**
 * Tema hijo Rehub – functions.php
 */

// 1) Carga la hoja de estilos del tema padre
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'rehub-parent-style',
        get_template_directory_uri() . '/style.css'
    );
} );

// 2) Traduce TODO lo que sea gettext (PHP)
add_filter( 'gettext', 'rehub_child_translate_all', 20, 3 );
function rehub_child_translate_all( $translated, $text, $domain ) {
    // Mapa de traducciones exactas
    $map = [
        'Search'                             => 'Buscar',
        'All categories'                     => 'Todas las categorías',
        'Shopping cart'                      => 'Carrito de Compras',
        'Wishlist'                           => 'Lista de deseos',
        'There is nothing in your wishlist'  => 'Tu lista de deseos está vacía.',
        'Manage Your Orders'                 => 'Gestionar mis pedidos',
        'Manage Your Shop'                   => 'Gestionar mi tienda',
        'Log out'                            => 'Cerrar sesión',
        'Logout'                             => 'Cerrar sesión',
        'Store details'                      => 'Detalles de la tienda',
        'Contacts'                           => 'Contacto',
        'Store Product Category'             => 'Categoría de productos',
        'Contact Vendor'                     => 'Contactar al vendedor',
        'Type your message...'               => 'Escribe tu mensaje...',
        'Add your review'                    => 'Agrega tu reseña',
        'Add to wishlist'                    => 'Agregar a favoritos',
        'Add to compare'                     => 'Agregar a comparar',
        'Ask owner'                          => 'Contactar al vendedor',
        'There are no reviews yet.'          => 'Aún no hay reseñas.',
        'Your Rating'                        => 'Tu valoración',
        'Your Review'                        => 'Tu reseña',
        'Pros:'                              => 'Ventajas:',
        'Cons:'                              => 'Desventajas:',
        'Submit'                             => 'Enviar',
        'User Reviews'                       => 'Reseñas de usuarios',
        'Write a Review'                     => 'Escribe una reseña',
        'WRITE A REVIEW'                     => 'Escribe una reseña',
    ];
    if ( isset( $map[ $text ] ) ) {
        return $map[ $text ];
    }

    // "Sold by %s"
    if ( $text === 'Sold by %s' ) {
        return 'Vendido por %s';
    }

    // "Be the first to review “Producto”"
    if ( strpos( $text, 'Be the first to review' ) === 0 ) {
        if ( preg_match( '/Be the first to review “(.+)”/', $text, $m ) ) {
            return sprintf( 'Sé el primero en reseñar "%s"', $m[1] );
        }
    }

    return $translated;
}

// 3) Traduce cadenas inyectadas vía JS o no gestionadas por gettext
add_action( 'wp_footer', 'rehub_child_translate_js', 100 );
function rehub_child_translate_js() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
      // Reemplazos globales en el HTML
      let html = document.body.innerHTML;

      html = html
        // 0.0 out of 5 → 0.0 de 5
        .replace(/\b([\d\.]+)\s*out of\s*([\d\.]+)\b/g, '$1 de $2')
        // User Reviews → Reseñas de usuarios (cualquier case)
        .replace(/User Reviews/gi, 'Reseñas de usuarios')
        // Write a Review / write a review / WRITE A REVIEW → Escribe una reseña
        .replace(/write a review/gi, 'Escribe una reseña')
        // Be the first to review → Sé el primero en reseñar
        .replace(/Be the first to review/gi, 'Sé el primero en reseñar')
        // Product Votes: X → Votos de producto: X
        .replace(/Product Votes:/gi, 'Votos de producto:')
        // Total submitted: X → Total enviado: X
        .replace(/Total submitted:/gi, 'Total enviado:')
        // Sold by → Vendido por
        .replace(/\bSold by\b/gi, 'Vendido por');

      document.body.innerHTML = html;
    });
    </script>
    <?php
}

// 4) Traduce el wizard de Dokan
add_filter( 'gettext', 'clscolombia_translate_dokan_wizard', 20, 3 );
function clscolombia_translate_dokan_wizard( $translated, $text, $domain ) {
    if ( strpos( $text, 'Thank you for choosing' ) !== false ) {
        return '¡Gracias por elegir The Marketplace para impulsar tu tienda en línea! '
             + 'Este asistente de configuración rápida te ayudará a configurar los ajustes básicos. '
             + 'Es completamente opcional y no debería tomar más de dos minutos.';
    }
    return $translated;
}

// 5) Rol Vendor Manager y permisos
add_action( 'init', function(){
    if ( ! get_role( 'vendor_manager' ) ) {
        add_role( 'vendor_manager', 'Vendor Manager', [
            'read' => true,
            'manage_woocommerce' => true,
            'edit_shop_orders' => true,
            'edit_products' => true,
            'edit_others_products' => true,
            'publish_products' => true,
            'delete_products' => true,
            'delete_others_products' => true,
            'delete_published_products' => true,
        ] );
    }
    // Asegura las caps
    $caps = [
      'read','manage_woocommerce','edit_shop_orders',
      'edit_products','edit_others_products','publish_products',
      'delete_products','delete_others_products','delete_published_products'
    ];
    $role = get_role( 'vendor_manager' );
    if ( $role ) {
        foreach ( $caps as $cap ) {
            $role->add_cap( $cap );
        }
    }
});

// 6) Ocultar menús para Vendor Manager
add_action( 'admin_menu', function(){
    if ( current_user_can( 'vendor_manager' ) ) {
        remove_menu_page('index.php');
        remove_menu_page('edit.php');
        remove_menu_page('upload.php');
        remove_menu_page('edit-comments.php');
        remove_menu_page('themes.php');
        remove_menu_page('plugins.php');
        remove_menu_page('users.php');
        remove_menu_page('tools.php');
        remove_menu_page('options-general.php');
    }
}, 999 );


// Función para modificar rol de usuario
add_action('wp_ajax_modificar_rol_usuario', 'modificar_rol_usuario_callback');

function modificar_rol_usuario_callback() {
    check_ajax_referer('modificar_rol_nonce', 'security');
    global $wpdb;

    // Sanitización segura de IDs
    $user = intval($_POST['userId']);
    $rol  = sanitize_text_field($_POST['rolCodigo']);

    // Obtener RolID por Código
    $rol_id = $wpdb->get_var(
        $wpdb->prepare("SELECT Id FROM bc_roles WHERE Codigo = %s", $rol)
    );
    
    // Verificar si exoste
    $existe = $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(*) FROM bc_user_role WHERE IdUser = %d", $user)
    );

    if ($existe > 0) {
        // Update si ya existe
        $resultado = $wpdb->update(
            'bc_user_role',
            ['IdRol' => $rol_id],
            ['IdUser' => $user],
            ['%d'],
            ['%d']
        );
    } else {
        // Insert si no existe
        $resultado = $wpdb->insert(
            'bc_user_role',
            ['IdUser' => $user, 'IdRol' => $rol_id],
            ['%d', '%d']
        );
    }

    if ($resultado !== false) {
        wp_send_json_success("Rol actualizado");
    } else {
        wp_send_json_error("Error de servidor");
    }
}
// END: Función para modificar rol de usuario


define('CLS_SECRET_KEY', '7i9FnaDgt917eRnzRo^>');

// Funciones JWT
function cls_base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function cls_create_jwt($payload, $secret) {
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $header_encoded = cls_base64url_encode(json_encode($header));
    $payload_encoded = cls_base64url_encode(json_encode($payload));
    $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", $secret, true);
    $signature_encoded = cls_base64url_encode($signature);
    return "$header_encoded.$payload_encoded.$signature_encoded";
}

// Login Endpoint para usar en GA
add_action('rest_api_init', function () {
    register_rest_route('clsapi/v1', '/login/', [
        'methods' => 'POST',
        'callback' => 'clsapi_login_callback',
        'permission_callback' => '__return_true'
    ]);
});

function clsapi_login_callback($request) {
    $params = $request->get_json_params();
    $username = sanitize_text_field($params['username'] ?? '');
    $password = $params['password'] ?? '';

    if (empty($username) || empty($password)) {
        return new WP_Error('invalid_request', 'Faltan credenciales.', ['status' => 400]);
    }

    $user = wp_authenticate($username, $password);
    if (is_wp_error($user)) {
        return new WP_Error('unauthorized', 'Usuario o contraseña inválidos.', ['status' => 403]);
    }

    $payload = [
        'user_id' => $user->ID,
        'username' => $user->user_login,
        'iat' => time(),
        'exp' => time() + 300 // 5 minutos de validez
    ];

    $token = cls_create_jwt($payload, CLS_SECRET_KEY);

    return [
        'token' => $token,
        'user' => [
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email
        ]
    ];
}
?>
