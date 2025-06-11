<?php
class UserHandler {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function readUsers($input) {
        $stmt = $this->pdo->query("SELECT id, username, role FROM usuarios ORDER BY username ASC");
        echo json_encode(['success' => true, 'users' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    public function saveUser($input) {
        $id = $input['id'] ?? null;
        $username = $input['username'];
        $role = $input['role'];
        $password = $input['password'];

        if (empty($username) || empty($role)) {
            throw new Exception('Usuário e permissão são obrigatórios.');
        }

        // Lógica para não rebaixar o último admin
        if ($id && $role === 'user' && $this->isLastAdmin($id)) {
            throw new Exception('Não é possível rebaixar o último administrador do sistema.');
        }

        if ($id) { // Atualizar
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $this->pdo->prepare("UPDATE usuarios SET username = ?, password = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $hashed_password, $role, $id]);
            } else {
                $stmt = $this->pdo->prepare("UPDATE usuarios SET username = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $role, $id]);
            }
            echo json_encode(['success' => true, 'message' => 'Usuário atualizado com sucesso!']);
        } else { // Criar
            if (empty($password)) {
                throw new Exception('A senha é obrigatória para criar um novo usuário.');
            }
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $role]);
            echo json_encode(['success' => true, 'message' => 'Usuário criado com sucesso!']);
        }
    }

    public function deleteUser($input) {
        $id = $input['id'];
        if ($id == $_SESSION['user_id']) {
            throw new Exception('Você não pode excluir seu próprio usuário.');
        }
        if ($this->isLastAdmin($id)) {
            throw new Exception('Não é possível excluir o último administrador do sistema.');
        }

        $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Usuário excluído com sucesso!']);
    }

    public function changePassword($input) {
        $user_id = $_SESSION['user_id'];
        $new_password = $input['new_password'];
        $confirm_password = $input['confirm_password'];

        if (empty($new_password) || $new_password !== $confirm_password) {
            throw new Exception('As senhas não correspondem ou estão vazias.');
        }
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $user_id]);

        echo json_encode(['success' => true, 'message' => 'Senha alterada com sucesso!']);
    }

    private function isLastAdmin($userId) {
        $stmt = $this->pdo->prepare("SELECT role FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user && $user['role'] === 'admin') {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM usuarios WHERE role = 'admin'");
            return $stmt->fetchColumn() <= 1;
        }
        return false;
    }
}