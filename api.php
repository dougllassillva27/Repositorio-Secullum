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
header('Content-Type: application/json');
require_once 'session.php';
require_once 'db_connection.php';
require_once 'api/UserHandler.php';
require_once 'api/LinkHandler.php';

$action = $_GET['action'] ?? null;
if (!$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ação não especificada.']);
    exit;
}

// Mapeamento de ações para seus respectivos handlers e permissões
$actionMap = [
    // Ações de Links e Categorias (Admin)
    'read_all'          => ['handler' => 'LinkHandler', 'method' => 'readAll',         'admin_only' => true],
    'save_order'        => ['handler' => 'LinkHandler', 'method' => 'saveOrder',       'admin_only' => true],
    'add_link'          => ['handler' => 'LinkHandler', 'method' => 'saveLink',        'admin_only' => true],
    'update_link'       => ['handler' => 'LinkHandler', 'method' => 'saveLink',        'admin_only' => true],
    'delete_link'       => ['handler' => 'LinkHandler', 'method' => 'deleteLink',      'admin_only' => true],
    'update_category'   => ['handler' => 'LinkHandler', 'method' => 'updateCategory',  'admin_only' => true],
    'delete_category'   => ['handler' => 'LinkHandler', 'method' => 'deleteCategory',  'admin_only' => true],
    // Ações de Usuários (Admin)
    'read_users'        => ['handler' => 'UserHandler', 'method' => 'readUsers',       'admin_only' => true],
    'save_user'         => ['handler' => 'UserHandler', 'method' => 'saveUser',        'admin_only' => true],
    'delete_user'       => ['handler' => 'UserHandler', 'method' => 'deleteUser',      'admin_only' => true],
    // Ações de Usuário Logado
    'change_password'   => ['handler' => 'UserHandler', 'method' => 'changePassword',  'admin_only' => false],
];

if (!array_key_exists($action, $actionMap)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ação desconhecida.']);
    exit;
}

$mapping = $actionMap[$action];

// Verificação de permissão centralizada
if ($mapping['admin_only'] && !is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Permissão de administrador necessária.']);
    exit;
}
if (!$mapping['admin_only'] && !is_logged_in()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Você precisa estar logado.']);
    exit;
}

try {
    $pdo = get_db_connection();
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Instancia o handler e chama o método apropriado
    $handlerClass = $mapping['handler'];
    $handlerMethod = $mapping['method'];
    
    $handler = new $handlerClass($pdo);
    $handler->$handlerMethod($input, $action); // Passamos a ação para métodos que tratam mais de um caso

} catch (PDOException $e) {
    http_response_code(500);
    // Em produção, seria bom logar o $e->getMessage() em vez de exibi-lo.
    echo json_encode(['success' => false, 'message' => 'Erro no servidor de banco de dados.']);
} catch (Exception $e) {
    // Captura exceções personalizadas dos handlers (ex: validação)
    http_response_code(400); 
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}