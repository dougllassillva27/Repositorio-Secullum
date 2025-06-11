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
$base = '/Secullum';

require_once 'session.php';
protect_page();
if (!is_admin()) {
    die('Acesso negado. Apenas administradores podem acessar esta página.');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="<?= versao("$base/admin.css") ?>">
    <link rel="stylesheet" href="<?= versao("$base/admin.css") ?>">
</head>
<body>
    <div class="container">
        <h1>Gerenciar Usuários</h1>
        <p>Crie, edite e remova usuários do sistema. <a href="gerenciar_links.php">(Gerenciar Links)</a> | <a href="index.php" target="_blank">(Ver página principal)</a></p>
        
        <div class="admin-main-content">
            <div id="form-container">
                <form id="form-usuario">
                    <h2 id="form-titulo">Adicionar Novo Usuário</h2>
                    <input type="hidden" id="edit-user-id">
                    <div class="form-grid">
                        <div class="form-group"><label for="user-username">Usuário:</label><input type="text" id="user-username" placeholder="Nome de usuário" required></div>
                        <div class="form-group"><label for="user-password">Nova Senha:</label><input type="password" id="user-password" placeholder="Deixe em branco para não alterar"></div>
                        <div class="form-group-full"><label>Permissão:</label><div class="radio-group"><input type="radio" id="role-user" name="role" value="user" checked> <label for="role-user">Usuário</label><input type="radio" id="role-admin" name="role" value="admin"> <label for="role-admin">Admin</label></div></div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" id="btn-add-update" class="btn btn-primary">Adicionar Usuário</button>
                        <button type="button" id="btn-cancel-edit" class="btn btn-secondary" style="display:none;">Cancelar</button>
                    </div>
                </form>
            </div>
            
            <div id="lista-usuarios-container">
                <h2>Usuários Existentes</h2>
                <div id="user-list"><p>Carregando usuários...</p></div>
            </div>
        </div> 
    </div>

    <div id="feedback-modal" class="modal-backdrop">
        <div class="modal-content">
            <h3 id="feedback-modal-title">Sucesso</h3>
            <p id="feedback-modal-message"></p>
            <div class="modal-actions">
                <button type="button" id="feedback-modal-ok" class="btn btn-sm btn-primary">OK</button>
            </div>
        </div>
    </div>

    <div id="confirm-modal" class="modal-backdrop">
        <div class="modal-content">
            <h3>Confirmação</h3>
            <p id="confirm-modal-message"></p>
            <div class="modal-actions">
                <button type="button" id="confirm-modal-cancel" class="btn btn-sm btn-secondary">Cancelar</button>
                <button type="button" id="confirm-modal-confirm" class="btn btn-sm btn-excluir">Confirmar</button>
            </div>
        </div>
    </div>
<script src="<?= versao("$base/utils.js") ?>"></script>
    <script src="<?= versao("$base/gerenciar_usuarios.js") ?>"></script>
</body>
</html>