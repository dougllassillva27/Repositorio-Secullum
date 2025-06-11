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

// Verificação de segurança ajustada:
// A ação 'change_password' pode ser executada por qualquer usuário logado.
// Todas as outras ações exigem permissão de administrador.
$action = $_GET['action'] ?? null;

if ($action === 'change_password') {
    if (!is_logged_in()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acesso negado. Você precisa estar logado para alterar sua senha.']);
        exit;
    }
} else {
    if (!is_admin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acesso negado. Permissão de administrador necessária para esta ação.']);
        exit;
    }
}

require_once 'db_connection.php';

$pdo = get_db_connection();
$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($action) {
        // --- AÇÕES DE GERENCIAMENTO DE LINKS E CATEGORIAS ---
        case 'read_all':
            $stmt_cat = $pdo->query("SELECT id, name, dashboard_name FROM categorias ORDER BY ordem ASC");
            $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
            $stmt_links = $pdo->prepare("SELECT * FROM links WHERE categoria_id = ? ORDER BY ordem ASC");
            foreach ($categories as $key => $category) {
                $stmt_links->execute([$category['id']]);
                $categories[$key]['links'] = $stmt_links->fetchAll(PDO::FETCH_ASSOC);
            }
            echo json_encode(['categories' => $categories]);
            break;

        case 'save_order':
            $pdo->beginTransaction();
            $stmt_cat = $pdo->prepare("UPDATE categorias SET ordem = ? WHERE id = ?");
            $stmt_link = $pdo->prepare("UPDATE links SET ordem = ?, categoria_id = ? WHERE id = ?");
            
            if (isset($input['categories'])) {
                foreach ($input['categories'] as $cat_order => $category) {
                    $stmt_cat->execute([$cat_order, $category['id']]);
                    if (isset($category['links'])) {
                        foreach ($category['links'] as $link_order => $link) {
                            $stmt_link->execute([$link_order, $category['id'], $link['id']]);
                        }
                    }
                }
            }
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Alterações salvas com sucesso!']);
            break;
        
        case 'add_link':
        case 'update_link':
            $categoria_id = $input['categoria_id'];
            if ($categoria_id === 'new') {
                $stmt = $pdo->prepare("INSERT INTO categorias (name, dashboard_name) VALUES (?, ?)");
                $stmt->execute([
                    $input['new_category_name'],
                    $input['new_category_dashboard_name']
                ]);
                $categoria_id = $pdo->lastInsertId();
            }

            $sql = $action === 'add_link'
                ? "INSERT INTO links (text, url, dashboard_text, icon, showOnDashboard, openInNewTab, visibilidade, categoria_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                : "UPDATE links SET text=?, url=?, dashboard_text=?, icon=?, showOnDashboard=?, openInNewTab=?, visibilidade=?, categoria_id=? WHERE id=?";
            
            $params = [
                $input['text'], $input['url'], $input['dashboard_text'], $input['icon'],
                (int)$input['showOnDashboard'], (int)$input['openInNewTab'], $input['visibilidade'], $categoria_id
            ];
            if ($action === 'update_link') $params[] = $input['id'];

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'message' => 'Link salvo com sucesso!']);
            break;

        case 'delete_link':
            $stmt = $pdo->prepare("DELETE FROM links WHERE id = ?");
            $stmt->execute([$input['id']]);
            echo json_encode(['success' => true, 'message' => 'Link excluído com sucesso!']);
            break;
        
        case 'update_category':
            $stmt = $pdo->prepare("UPDATE categorias SET name = ?, dashboard_name = ? WHERE id = ?");
            $stmt->execute([
                $input['name'],
                $input['dashboard_name'],
                $input['id']
            ]);
            echo json_encode(['success' => true, 'message' => 'Categoria atualizada com sucesso!']);
            break;

        case 'delete_category':
             $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
             $stmt->execute([$input['id']]);
             echo json_encode(['success' => true, 'message' => 'Categoria excluída com sucesso!']);
             break;

        // --- AÇÕES DE GERENCIAMENTO DE USUÁRIOS ---
        case 'read_users':
            $stmt = $pdo->query("SELECT id, username, role FROM usuarios ORDER BY username ASC");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'users' => $users]);
            break;

        case 'save_user':
            $id = $input['id'] ?? null;
            $username = $input['username'];
            $role = $input['role'];
            $password = $input['password'];

            if (empty($username) || empty($role)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Usuário e permissão são obrigatórios.']);
                break;
            }

            if ($id && $role === 'user') {
                $stmt = $pdo->prepare("SELECT role FROM usuarios WHERE id = ?");
                $stmt->execute([$id]);
                $currentUser = $stmt->fetch();
                if ($currentUser['role'] === 'admin') {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE role = 'admin'");
                    $adminCount = $stmt->fetchColumn();
                    if ($adminCount <= 1) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Não é possível rebaixar o último administrador do sistema.']);
                        break;
                    }
                }
            }

            if ($id) { // Atualizar usuário
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE usuarios SET username = ?, password = ?, role = ? WHERE id = ?");
                    $stmt->execute([$username, $hashed_password, $role, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE usuarios SET username = ?, role = ? WHERE id = ?");
                    $stmt->execute([$username, $role, $id]);
                }
                echo json_encode(['success' => true, 'message' => 'Usuário atualizado com sucesso!']);
            } else { // Criar novo usuário
                if (empty($password)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'A senha é obrigatória para criar um novo usuário.']);
                    break;
                }
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, role) VALUES (?, ?, ?)");
                $stmt->execute([$username, $hashed_password, $role]);
                echo json_encode(['success' => true, 'message' => 'Usuário criado com sucesso!']);
            }
            break;

        case 'delete_user':
            $id = $input['id'];
            if ($id == $_SESSION['user_id']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Você não pode excluir seu próprio usuário.']);
                break;
            }
            $stmt = $pdo->prepare("SELECT role FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            $userToDelete = $stmt->fetch();
            if ($userToDelete['role'] === 'admin') {
                 $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE role = 'admin'");
                 $adminCount = $stmt->fetchColumn();
                 if ($adminCount <= 1) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Não é possível excluir o último administrador do sistema.']);
                    break;
                 }
            }
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Usuário excluído com sucesso!']);
            break;
            
        // --- AÇÃO DE ALTERAR A PRÓPRIA SENHA ---
        case 'change_password':
            $user_id = $_SESSION['user_id'];
            $new_password = $input['new_password'];
            $confirm_password = $input['confirm_password'];

            if (empty($new_password) || empty($confirm_password)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios.']);
                break;
            }

            if ($new_password !== $confirm_password) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'A nova senha e a confirmação não correspondem.']);
                break;
            }
            
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $update_stmt->execute([$new_hashed_password, $user_id]);

            echo json_encode(['success' => true, 'message' => 'Senha alterada com sucesso!']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ação desconhecida.']);
            break;
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
}
?>