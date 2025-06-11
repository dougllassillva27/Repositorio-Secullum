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
/**
 * Funções Utilitárias Compartilhadas
 */

/**
 * Realiza uma chamada padronizada para a API.
 * @param {string} action - A ação a ser executada na API.
 * @param {string} method - O método HTTP (GET, POST, etc.).
 * @param {object|null} body - O corpo da requisição para métodos POST.
 * @returns {Promise<object|null>} - O resultado da API ou null em caso de erro.
 */
async function apiCall(action, method = 'POST', body = null) {
  try {
    const options = { method, headers: { 'Content-Type': 'application/json' } };
    if (body) {
      options.body = JSON.stringify(body);
    }
    const response = await fetch(`api.php?action=${action}`, options);
    const result = await response.json();
    if (!response.ok) {
      // Usa a mensagem de erro da API ou um erro padrão
      throw new Error(result.message || `Erro ${response.status}: ${response.statusText}`);
    }
    return result;
  } catch (error) {
    // Exibe o erro no modal de feedback genérico
    showFeedbackModal(error.message, 'Erro de Comunicação');
    return null;
  }
}

/**
 * Exibe um modal de feedback para o usuário.
 * @param {string} message - A mensagem a ser exibida.
 * @param {string} [title='Sucesso'] - O título do modal.
 */
function showFeedbackModal(message, title = 'Sucesso') {
  const modal = document.getElementById('feedback-modal');
  if (!modal) return;

  document.getElementById('feedback-modal-title').textContent = title;
  document.getElementById('feedback-modal-message').textContent = message;
  modal.style.display = 'flex';
  document.getElementById('feedback-modal-ok').focus();
}

/**
 * Fecha o modal de feedback.
 */
function closeFeedbackModal() {
  const modal = document.getElementById('feedback-modal');
  if (modal) modal.style.display = 'none';
}

/**
 * Exibe um modal de confirmação antes de uma ação destrutiva.
 * @param {string} message - A mensagem de confirmação.
 * @param {function} onConfirm - A função a ser executada se o usuário confirmar.
 */
function showConfirmModal(message, onConfirm) {
  const modal = document.getElementById('confirm-modal');
  if (!modal) return;

  document.getElementById('confirm-modal-message').textContent = message;
  modal.style.display = 'flex';

  const confirmBtn = document.getElementById('confirm-modal-confirm');

  // Clona o botão para remover event listeners antigos e evitar execuções múltiplas
  const newConfirmBtn = confirmBtn.cloneNode(true);
  confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

  newConfirmBtn.addEventListener(
    'click',
    () => {
      closeConfirmModal();
      onConfirm();
    },
    { once: true }
  ); // Garante que o evento seja executado apenas uma vez
}

/**
 * Fecha o modal de confirmação.
 */
function closeConfirmModal() {
  const modal = document.getElementById('confirm-modal');
  if (modal) modal.style.display = 'none';
}

// Adiciona eventos globais para fechar os modais (se existirem na página)
document.addEventListener('DOMContentLoaded', () => {
  const feedbackModal = document.getElementById('feedback-modal');
  if (feedbackModal) {
    document.getElementById('feedback-modal-ok').addEventListener('click', closeFeedbackModal);
    feedbackModal.addEventListener('click', (e) => {
      if (e.target === feedbackModal) closeFeedbackModal();
    });
  }

  const confirmModal = document.getElementById('confirm-modal');
  if (confirmModal) {
    document.getElementById('confirm-modal-cancel').addEventListener('click', closeConfirmModal);
    confirmModal.addEventListener('click', (e) => {
      if (e.target === confirmModal) closeConfirmModal();
    });
  }
});
