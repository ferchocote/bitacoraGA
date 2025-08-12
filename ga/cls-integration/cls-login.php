<?php
/*
    Plugin Name: CLS Colombia Login Integration
    Description: Muestra el formulario de login para acceder a CLS Colombia desde GA.
    Version: 1.0
    Author: David Bonilla
*/

add_shortcode('login_clscolombia', 'ga_login_form_shortcode');

function ga_login_form_shortcode() {
    ob_start(); ?>

    <style>
            /* Estilos del LOGIN */
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f5f6fa;
            color: #333;
            font-size: 14px;
        }

        .top-main-menu {
            background-color: #2c3e50;
            color: #fff;
            text-align: right;
            padding: 0 20px;
        }

        .login-body * {
            box-sizing: border-box;
        }

        .login-body {
        background-color: #f4f6f9;
        display: flex;
        justify-content: center;
        align-items: center;
        height: calc(100vh - 32px);
        }

        .container {
        display: flex;
        width: 900px;
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        }

        .left {
        background-color: #2c3e50;
        color: #fff;
        padding: 20px 30px 40px 30px;
        width: 50%;
        }

        .left h2 {
        margin-bottom: 20px;
        }

        .left p {
        font-size: 14px;
        line-height: 1.6;
        }

        .right {
        padding: 20px 30px 40px 30px;
        width: 50%;
        }

        .right h2 {
        margin-bottom: 10px;
        }

        .right p {
        font-size: 14px;
        margin-bottom: 20px;
        color: #333;
        }

        .form-group {
        margin-bottom: 15px;
        }

        .form-group label {
        display: block;
        font-size: 14px;
        margin-bottom: 5px;
        }

        .form-group input[type="email"],
        .form-group input[type="password"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        }

        .l-btn {
        width: 100%;
        background-color: #0066ff;
        color: white;
        margin-top: 10px;
        padding: 10px;
        border: none;
        border-radius: 5px;
        font-weight: bold;
        cursor: pointer;
        }

        .l-btn:hover {
        background-color: #004ecc;
        }
        /* Estilos del LOGIN */
    </style>

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
                <form id="cls-login-form">
                    <div class="form-group">
                    <label for="cls-username">Email</label>
                    <input type="email" id="cls-username" name="username" placeholder="tu@email.com" required>
                    </div>
                    <div class="form-group">
                    <label for="cls-password">Contraseña</label>
                    <input type="password" id="cls-password" name="password" placeholder="********" required>
                    </div>

                    <button type="submit" class="l-btn" name="wp-submit">Ingresar</button>
                    <p id="cls-login-error" style="color: red;"></p>
                </form>
                </div>
            </div>
        </div>
    </body>
    
    <script>
        document.getElementById('cls-login-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const username = document.getElementById('cls-username').value.trim();
            const password = document.getElementById('cls-password').value;
            const errorEl = document.getElementById('cls-login-error');
            errorEl.textContent = '';

            if (!username || !password) {
                errorEl.textContent = 'Por favor, completa todos los campos.';
                return;
            }

            try {
                const response = await fetch('https://clscolombia.com/wp-json/clsapi/v1/login/', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ username, password })
                });

                const data = await response.json();

                if (response.ok && data.token) {
                    // 🔁 Redirigir al sistema CLSColombia con el token
                    window.location.href = `https://clscolombia.com/wp-content/bitacoras/?view=bitacoras&token=${encodeURIComponent(data.token)}`;
                } else {
                    errorEl.textContent = data.message || 'Usuario o contraseña inválidos.';
                }

            } catch (err) {
                console.error('Error al conectar con clscolombia:', err);
                errorEl.textContent = 'Hubo un error al conectar con el sistema. Intenta nuevamente.';
            }
        });
    </script>

    <?php
    return ob_get_clean();
}
