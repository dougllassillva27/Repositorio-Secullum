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
    <title>Gerenciar Links</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="<?= versao("$base/gerenciar_links.css") ?>">
</head>
<body>
    <div class="container">
        <h1>Gerenciar Links e Categorias</h1>
        <p>Arraste os itens para reordenar. <a href="index.php" target="_blank">(Ver página principal)</a> | <a href="gerenciar_usuarios.php">(Gerenciar Usuários)</a></p>
        
        <button id="btn-salvar-alteracoes" class="btn btn-salvar">Salvar Alterações</button>

        <div class="admin-main-content">
            <div id="form-container">
                <form id="form-novo-link">
                    <h2 id="form-titulo">Adicionar Novo Link</h2>
                    <input type="hidden" id="edit-link-id">
                    <div class="form-grid">
                        <div class="form-group"><label for="link-text">Texto do Link (menu):</label><input type="text" id="link-text" placeholder="Ex: Planilha de Controle"></div>
                        <div class="form-group"><label for="link-dashboard-text">Texto do Link (painel):</label><input type="text" id="link-dashboard-text" placeholder="Ex: Planilha Principal"></div>
                        <div class="form-group-full"><label for="link-url">URL:</label><input type="url" id="link-url" placeholder="https://..."></div>
                        <div class="form-group-full"><label for="link-icon">Classe do Ícone (Font Awesome):</label><input type="text" id="link-icon" placeholder="Ex: fa-link, fa-home"><p class="form-help-text">Encontre ícones no <a href="https://fontawesome.com/v5/search?m=free" target="_blank">repositório oficial</a>.</p></div>
                        <div class="form-group"><label for="link-category">Categoria Existente:</label><select id="link-category"></select></div>
                        
                        <div class="form-group">
                            <label for="link-new-category">Ou Nova Categoria (Nome Menu):</label>
                            <input type="text" id="link-new-category" placeholder="Nome curto para o menu">
                        </div>
                        <div class="form-group">
                            <label for="link-new-category-dashboard">Nova Categoria (Nome Painel):</label>
                            <input type="text" id="link-new-category-dashboard" placeholder="Nome completo para o painel">
                        </div>

                        <div class="form-group-full"><label>Visibilidade:</label><div class="radio-group"><input type="radio" id="vis-all" name="visibilidade" value="all" checked> <label for="vis-all">Todos</label><input type="radio" id="vis-admin" name="visibilidade" value="admin"> <label for="vis-admin">Admins</label></div></div>
                        <div class="checkbox-group"><div><input type="checkbox" id="link-show-on-dashboard" checked><label for="link-show-on-dashboard">Mostrar no Painel</label></div><div><input type="checkbox" id="link-open-in-new-tab"><label for="link-open-in-new-tab">Abrir em Nova Aba</label></div></div>
                    </div>
                    <div class="form-actions">
                        <button type="button" id="btn-add-update" class="btn btn-primary">Adicionar Link</button>
                        <button type="button" id="btn-cancel-edit" class="btn btn-secondary" style="display:none;">Cancelar</button>
                    </div>
                </form>
            </div>
            <div id="gerenciador-links"><p>Carregando links...</p></div>
        </div> 
    </div>

    <div id="category-edit-modal" class="modal-backdrop">
        <div class="modal-content">
            <h3>Editar Nomes da Categoria</h3>
            <div class="form-group">
                <label for="modal-category-name-input">Nome no Menu:</label>
                <input type="text" id="modal-category-name-input" placeholder="Nome que aparece na sidebar">
            </div>
            <div class="form-group">
                <label for="modal-category-dashboard-name-input">Nome no Painel:</label>
                <input type="text" id="modal-category-dashboard-name-input" placeholder="Título que aparece no painel principal">
            </div>
            <div class="modal-actions">
                <button type="button" id="modal-cancel-button" class="btn btn-sm btn-secondary">Cancelar</button>
                <button type="button" id="modal-save-button" class="btn btn-sm btn-editar">Salvar</button>
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

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script src="<?= versao("$base/gerenciar_links.js") ?>"></script>
</body>
</html>