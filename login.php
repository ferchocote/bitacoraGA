<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Bitácoras</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
<div class="top-main-menu">
    <div style="height: 32px;"></div>
</div>
  <div class="login-body">
  <div class="container">
    <div class="left">
      <h2>Bienvenido</h2>
      <p>Inicia sesión para acceder a tu panel de control. <br> <br>
         Si aún no tienes una cuenta, contacta a un administrador para la creación de una.</p>
    </div>
        <div class="right">
          <h2>Iniciar Sesión</h2>
          <p>Por favor, introduce tus credenciales.</p>
          <form method="post" action="">
            <div class="form-group">
              <label for="email">Email</label>
              <input type="email" id="email" name="email" placeholder="tu@email.com">
            </div>
            <div class="form-group">
              <label for="password">Contraseña</label>
              <input type="password" id="password" name="password" placeholder="********">
            </div>
            <?php
            require_once('../../wp-load.php');
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wp-submit'])) {
                $creds = array(
                    'user_login'    => $_POST['email'],
                    'user_password' => $_POST['password']
                );
            
                // Intenta autenticar
                $user = wp_signon($creds, false);
                $referer = $_SERVER['HTTP_REFERER'] ?? '';
                $current_url = home_url( add_query_arg( null, null ) );

            
                if (is_wp_error($user)) {
                    echo '<p style="color:red;">' . $user->get_error_message() . '</p>';
                } else {
                    echo "<script>
                        if (window.top !== window.self) {
                            window.top.location.href = 'https://clscolombia.com/wp-content/bitacoras/?view=bitacoras';
                        }
                    </script>";
                    exit;
                }
            }
            
            ?>
            <button type="submit" class="l-btn" name="wp-submit">Ingresar</button>
          </form>
        </div>
      </div>
    </div>
</body>
</html>