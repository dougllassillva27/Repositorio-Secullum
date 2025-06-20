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
document.addEventListener('DOMContentLoaded', () => {
  // --- Referências ao DOM ---
  const menuButton = document.getElementById('menu-button');
  const sidebar = document.getElementById('sidebar');
  const contentFrame = document.getElementById('contentFrame');
  const dashboardContent = document.getElementById('dashboard-content');
  const footer = document.getElementById('footer');
  const btnChangePassword = document.getElementById('btn-change-password');
  const changePasswordModal = document.getElementById('change-password-modal');
  const changePasswordCancelBtn = document.getElementById('change-password-cancel');
  const changePasswordSaveBtn = document.getElementById('change-password-save');
  const inputNewPassword = document.getElementById('new_password');
  const inputConfirmPassword = document.getElementById('confirm_password');
  const tooltip = document.createElement('div');
  tooltip.id = 'js-tooltip';
  document.body.appendChild(tooltip);

  // --- REFERÊNCIAS PARA O TEMA ---
  const themeCheckbox = document.getElementById('theme-checkbox');
  const themeSwitchWrapper = document.querySelector('.theme-switch-wrapper');

  // ========================================================================
  // LÓGICA DE PERSISTÊNCIA DE ESTADO (TEMA E SIDEBAR)
  // ========================================================================
  const THEME_KEY = 'theme-preference';
  const SIDEBAR_STATE_KEY = 'sidebar-state';
  const rootElement = document.documentElement;

  /**
   * Aplica o tema (claro ou escuro) e sincroniza o estado do interruptor.
   * @param {string} theme - O tema a ser aplicado ('dark' or 'light').
   */
  const applyTheme = (theme) => {
    const isDark = theme === 'dark';
    rootElement.classList.toggle('dark-mode', isDark);
    if (themeCheckbox) {
      themeCheckbox.checked = isDark;
    }
  };

  // Adiciona o evento de 'change' ao checkbox do tema, se ele existir.
  if (themeCheckbox) {
    themeCheckbox.addEventListener('change', () => {
      const newTheme = themeCheckbox.checked ? 'dark' : 'light';
      localStorage.setItem(THEME_KEY, newTheme);
      applyTheme(newTheme);
    });
  }

  // Ao carregar a página, aplica o tema salvo para consistência.
  const savedTheme = localStorage.getItem(THEME_KEY) || 'light';
  applyTheme(savedTheme);

  // Ao carregar a página, aplica o estado da sidebar salvo.
  const savedSidebarState = localStorage.getItem(SIDEBAR_STATE_KEY);
  if (savedSidebarState === 'minimized') {
    if (sidebar) sidebar.classList.add('minimized');
  }

  // ========================================================================
  // LÓGICA DA APLICAÇÃO
  // ========================================================================

  // --- Lógica dos Modais ---
  const feedbackModal = document.getElementById('feedback-modal');
  const feedbackModalTitle = document.getElementById('feedback-modal-title');
  const feedbackModalMessage = document.getElementById('feedback-modal-message');
  const feedbackModalOkBtn = document.getElementById('feedback-modal-ok');

  function showFeedbackModal(message, title = 'Sucesso') {
    if (feedbackModal) {
      feedbackModalTitle.textContent = title;
      feedbackModalMessage.textContent = message;
      feedbackModal.style.display = 'flex';
      feedbackModalOkBtn.focus();
    }
  }
  function closeFeedbackModal() {
    if (feedbackModal) feedbackModal.style.display = 'none';
  }

  function openChangePasswordModal() {
    inputNewPassword.value = '';
    inputConfirmPassword.value = '';
    if (changePasswordModal) {
      changePasswordModal.style.display = 'flex';
      inputNewPassword.focus();
    }
  }
  function closeChangePasswordModal() {
    if (changePasswordModal) changePasswordModal.style.display = 'none';
  }

  async function saveNewPassword() {
    const new_password = inputNewPassword.value;
    const confirm_password = inputConfirmPassword.value;

    if (!new_password || !confirm_password) {
      showFeedbackModal('Por favor, preencha todos os campos.', 'Atenção');
      return;
    }
    if (new_password !== confirm_password) {
      showFeedbackModal('A nova senha e a confirmação não correspondem.', 'Atenção');
      return;
    }
    const result = await apiCall('change_password', 'POST', {
      new_password,
      confirm_password,
    });
    if (result && result.success) {
      closeChangePasswordModal();
      showFeedbackModal(result.message, 'Sucesso');
    } else if (result) {
      showFeedbackModal(result.message, 'Erro');
    }
  }

  // --- Função genérica de API ---
  async function apiCall(action, method = 'POST', body = null) {
    try {
      const options = { method, headers: { 'Content-Type': 'application/json' } };
      if (body) options.body = JSON.stringify(body);
      const response = await fetch(`api.php?action=${action}`, options);
      const result = await response.json();
      if (!response.ok) {
        throw new Error(result.message || 'Ocorreu um erro desconhecido.');
      }
      return result;
    } catch (error) {
      showFeedbackModal(error.message, 'Erro de Comunicação');
      return null;
    }
  }

  // --- Lógica de Roteamento e UI ---
  function toggleMenuState() {
    if (sidebar) {
      sidebar.classList.toggle('minimized');
      const newState = sidebar.classList.contains('minimized') ? 'minimized' : 'maximized';
      localStorage.setItem(SIDEBAR_STATE_KEY, newState);
    }
  }

  function showDashboard() {
    if (dashboardContent) dashboardContent.classList.remove('d-none');
    if (themeSwitchWrapper) themeSwitchWrapper.classList.remove('d-none');
    if (footer) footer.classList.remove('d-none');

    if (contentFrame) {
      contentFrame.classList.add('d-none');
      contentFrame.src = 'about:blank';
    }
    document.title = 'Painel - Repositório Secullum';
  }

  function showIframe(url, clickedElement) {
    if (dashboardContent) dashboardContent.classList.add('d-none');
    if (themeSwitchWrapper) themeSwitchWrapper.classList.add('d-none');
    if (footer) footer.classList.add('d-none');

    if (contentFrame) {
      contentFrame.classList.remove('d-none');
      try {
        contentFrame.contentWindow.location.replace(url);
      } catch (e) {
        contentFrame.src = url;
      }
    }
    document.title = (clickedElement.dataset.tooltip || clickedElement.textContent.trim()) + ' - Repositório Secullum';
  }

  function rotear(state) {
    const slug = state ? state.slug : window.location.hash.substring(1) || 'home';
    if (slug === 'home') {
      showDashboard();
    } else {
      const linkElement = document.querySelector(`a[data-slug="${slug}"]`);
      if (linkElement && linkElement.hasAttribute('data-url')) {
        showIframe(linkElement.dataset.url, linkElement);
      } else {
        showDashboard();
      }
    }
  }

  function handleLinkClick(event) {
    const linkElement = event.currentTarget;
    if (!linkElement.hasAttribute('data-slug')) return;
    event.preventDefault();
    if (linkElement.dataset.openInNewTab === 'true') {
      window.open(linkElement.dataset.url, '_blank');
      return;
    }
    const slug = linkElement.dataset.slug;
    const url = `#${slug}`;
    if (window.location.hash === url) return;
    const state = { slug: slug };
    history.pushState(state, '', url);
    rotear(state);
  }

  // --- INICIALIZAÇÃO E EVENTOS ---
  if (menuButton) {
    menuButton.addEventListener('click', toggleMenuState);
  }
  if (btnChangePassword) {
    btnChangePassword.addEventListener('click', openChangePasswordModal);
  }
  if (changePasswordCancelBtn) {
    changePasswordCancelBtn.addEventListener('click', closeChangePasswordModal);
  }
  if (changePasswordSaveBtn) {
    changePasswordSaveBtn.addEventListener('click', saveNewPassword);
  }
  if (feedbackModalOkBtn) {
    feedbackModalOkBtn.addEventListener('click', closeFeedbackModal);
  }
  if (feedbackModal) {
    feedbackModal.addEventListener('click', (e) => {
      if (e.target === feedbackModal) closeFeedbackModal();
    });
  }
  document.addEventListener('keyup', (e) => {
    if (feedbackModal && feedbackModal.style.display === 'flex' && (e.key === 'Enter' || e.key === 'Escape')) {
      closeFeedbackModal();
    }
  });

  document.querySelectorAll('.sidebar-link, .dashboard-link-list a').forEach((link) => {
    link.addEventListener('click', handleLinkClick);
  });
  window.addEventListener('popstate', (e) => rotear(e.state));

  function setupTooltips() {
    document.querySelectorAll('#sidebar a[data-tooltip]').forEach((l) => {
      l.addEventListener('mouseenter', handleTooltipShow);
      l.addEventListener('mouseleave', handleTooltipHide);
    });
  }
  function handleTooltipShow(e) {
    if (sidebar && sidebar.classList.contains('minimized')) {
      const t = e.currentTarget;
      tooltip.textContent = t.dataset.tooltip;
      const n = t.getBoundingClientRect();
      tooltip.style.top = `${n.top + n.height / 2}px`;
      tooltip.style.left = `${n.right + 12}px`;
      tooltip.style.transform = 'translateY(-50%)';
      tooltip.classList.add('visible');
    }
  }
  function handleTooltipHide() {
    tooltip.classList.remove('visible');
  }
  setupTooltips();

  const initialSlug = window.location.hash.substring(1) || 'home';
  history.replaceState({ slug: initialSlug }, '', `#${initialSlug}`);
  rotear({ slug: initialSlug });

  // --- OUVINTE PARA AÇÕES VINDAS DO IFRAME ---
  window.addEventListener('message', (event) => {
    // Verificação de segurança crucial: só aceita mensagens do seu próprio domínio
    if (event.origin !== 'https://dougllassillva27.com.br') {
      console.warn('Mensagem bloqueada de origem não confiável:', event.origin);
      return;
    }

    // Verifica se a mensagem tem o formato esperado
    if (event.data && event.data.type === 'openReport' && event.data.url) {
      // A página principal, que tem permissão, abre a nova guia
      console.log('Recebida solicitação para abrir relatório do iframe:', event.data.url);
      window.open(event.data.url, '_blank');
    }
  });
});
