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
 */ /**
 * Lógica específica para a página de Gerenciamento de Usuários.
 * Depende de utils.js para funções de API e modais.
 */
document.addEventListener('DOMContentLoaded', () => {
  // --- Elementos do DOM ---
  const userListDiv = document.getElementById('user-list');
  const form = document.getElementById('form-usuario');
  const formTitle = document.getElementById('form-titulo');
  const btnAddUpdate = document.getElementById('btn-add-update');
  const btnCancelEdit = document.getElementById('btn-cancel-edit');
  const inputUserId = document.getElementById('edit-user-id');
  const inputUsername = document.getElementById('user-username');
  const inputPassword = document.getElementById('user-password');

  // --- Funções Específicas da Página ---

  async function carregarUsuarios() {
    // A função 'apiCall' agora vem de utils.js
    const response = await apiCall('read_users', 'GET');
    if (response && response.success) {
      renderizarUsuarios(response.users);
    }
  }

  function renderizarUsuarios(users) {
    userListDiv.innerHTML = '';
    if (users.length === 0) {
      userListDiv.innerHTML = '<p>Nenhum usuário encontrado.</p>';
      return;
    }
    users.forEach((user) => {
      const userItem = document.createElement('div');
      userItem.className = 'user-item';
      userItem.dataset.userid = user.id;
      userItem.innerHTML = `
        <div class="user-info">
          <strong>${user.username}</strong>
          <span class="role ${user.role}">${user.role}</span>
        </div>
        <div class="user-actions">
          <button class="btn btn-sm btn-editar">Editar</button>
          <button class="btn btn-sm btn-excluir">Excluir</button>
        </div>
      `;
      userListDiv.appendChild(userItem);
    });
    addEventListeners();
  }

  function addEventListeners() {
    document.querySelectorAll('.user-item .btn-editar').forEach((btn) => {
      btn.addEventListener('click', handleEditClick);
    });
    document.querySelectorAll('.user-item .btn-excluir').forEach((btn) => {
      btn.addEventListener('click', handleDeleteClick);
    });
  }

  function handleEditClick(e) {
    const userItem = e.target.closest('.user-item');
    const userId = userItem.dataset.userid;
    const username = userItem.querySelector('.user-info strong').textContent;
    const role = userItem.querySelector('.user-info .role').textContent;

    formTitle.textContent = 'Editar Usuário';
    btnAddUpdate.textContent = 'Salvar Alteração';
    btnCancelEdit.style.display = 'inline-block';

    inputUserId.value = userId;
    inputUsername.value = username;
    document.querySelector(`input[name="role"][value="${role}"]`).checked = true;
    inputPassword.value = '';
    inputPassword.placeholder = 'Deixe em branco para não alterar';

    form.scrollIntoView({ behavior: 'smooth' });
  }

  async function handleDeleteClick(e) {
    const userItem = e.target.closest('.user-item');
    const userId = userItem.dataset.userid;
    const username = userItem.querySelector('strong').textContent;
    const message = `Tem certeza que deseja excluir o usuário "${username}"? Esta ação não pode ser desfeita.`;

    // A função 'showConfirmModal' agora vem de utils.js
    showConfirmModal(message, async () => {
      const result = await apiCall('delete_user', 'POST', { id: userId });
      if (result && result.success) {
        // A função 'showFeedbackModal' agora vem de utils.js
        showFeedbackModal(result.message);
        carregarUsuarios();
      }
    });
  }

  function resetarFormulario() {
    form.reset();
    formTitle.textContent = 'Adicionar Novo Usuário';
    btnAddUpdate.textContent = 'Adicionar Usuário';
    btnCancelEdit.style.display = 'none';
    inputUserId.value = '';
    inputPassword.placeholder = 'Senha para novo usuário';
  }

  // --- Inicialização e Eventos da Página ---

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const userData = {
      id: inputUserId.value || null,
      username: inputUsername.value,
      password: inputPassword.value,
      role: document.querySelector('input[name="role"]:checked').value,
    };
    const result = await apiCall('save_user', 'POST', userData);
    if (result && result.success) {
      showFeedbackModal(result.message);
      resetarFormulario();
      carregarUsuarios();
    }
  });

  btnCancelEdit.addEventListener('click', resetarFormulario);

  // Carga inicial dos dados
  carregarUsuarios();
});
