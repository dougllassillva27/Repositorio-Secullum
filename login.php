<?php
/**
 * MIT License
 *
 * Copyright (c) 2025 Douglas Silva
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/inc/versao.php';
$base = '/Secullum'; // Ou o valor correto para a sua aplicação

// A primeira coisa que fazemos é chamar nosso session.php.
// Isso garante que session_name() seja chamado ANTES de session_start().
require_once 'session.php'; 
require_once 'db_connection.php';

// Se já estiver logado (na sessão correta), redireciona para o index
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error_message = '';
$initial_setup_message = '';

try {
    $pdo = get_db_connection();
    
    // --- LÓGICA PARA CRIAR O PRIMEIRO ADMIN ---
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE role = 'admin'");
    $stmt->execute();
    $admin_count = $stmt->fetchColumn();

    if ($admin_count == 0) {
        $username = 'admin';
        $password = 'admin123'; // Senha padrão inicial
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $insert_stmt = $pdo->prepare("INSERT INTO usuarios (username, password, role) VALUES (?, ?, 'admin')");
        $insert_stmt->execute([$username, $hashed_password]);
        
        $initial_setup_message = "Primeiro acesso detectado. Um usuário administrador foi criado. <br><b>Usuário:</b> admin <br><b>Senha:</b> admin123 <br>Por favor, altere a senha após o login.";
    }
    // --- FIM DA LÓGICA DO PRIMEIRO ADMIN ---

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error_message = 'Por favor, preencha o usuário e a senha.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Login bem-sucedido
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header('Location: index.php');
                exit;
            } else {
                $error_message = 'Usuário ou senha inválidos.';
            }
        }
    }
} catch (PDOException $e) {
    $error_message = "Erro de conexão com o banco de dados. Contate o administrador.";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- Meta Tags Básicas -->
    <meta name="description" content="Secullum Repositório" />
    <meta name="keywords" content="site, links, lanches, Secullum" />
    <meta name="author" content="Douglas Silva" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Open Graph / Facebook / WhatsApp -->
    <meta property="og:title" content="Secullum Repositório" />
    <meta property="og:description" content="Secullum Repositório" />
    <meta property="og:url" content="https://www.dougllassillva27.com.br" />
    <meta property="og:type" content="website" />
    <meta property="og:image" content="https://dougllassillva27.com.br/<?= versao("$base/logo-social-share.webp") ?>">
    <meta property="og:image:width" content="512" />
    <meta property="og:image:height" content="512" />
    <meta property="og:site_name" content="Secullum Repositório" />

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="Secullum Repositório" />
    <meta name="twitter:description" content="Secullum Repositório" />

    <title>Login - Repositório Secullum</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        h1 { color: #0056b3; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; text-align: left; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #495057; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 4px; font-size: 1em; cursor: pointer; transition: background-color 0.2s; }
        button:hover { background-color: #0056b3; }
        .error { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-top: 20px; }
        .info { color: #0c5460; background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Repositório Secullum</h1>
        
        <?php if (!empty($initial_setup_message)): ?>
            <div class="info"><?= $initial_setup_message ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Usuário</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Entrar</button>
        </form>

        <?php if ($error_message): ?>
            <div class="error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
    </div>
</body>
</html>