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
 * Lógica específica para a página de Gerenciamento de Links e Categorias.
 * Depende de utils.js para funções de API e modais.
 */
document.addEventListener('DOMContentLoaded', () => {
  // --- Referências ao DOM ---
  const linkListContainer = document.getElementById('gerenciador-links');
  const btnSalvarAlteracoes = document.getElementById('btn-salvar-alteracoes');
  const form = document.getElementById('form-novo-link');
  const formTitle = document.getElementById('form-titulo');
  const btnAddUpdate = document.getElementById('btn-add-update');
  const btnCancelEdit = document.getElementById('btn-cancel-edit');
  const inputLinkId = document.getElementById('edit-link-id');
  const inputLinkText = document.getElementById('link-text');
  const inputDashboardText = document.getElementById('link-dashboard-text');
  const inputLinkUrl = document.getElementById('link-url');
  const inputLinkIcon = document.getElementById('link-icon');
  const selectCategory = document.getElementById('link-category');
  const inputNewCategory = document.getElementById('link-new-category');
  const inputNewCategoryDashboard = document.getElementById('link-new-category-dashboard');
  const checkShowOnDashboard = document.getElementById('link-show-on-dashboard');
  const checkOpenInNewTab = document.getElementById('link-open-in-new-tab');
  const categoryModal = document.getElementById('category-edit-modal');
  const categoryModalInput = document.getElementById('modal-category-name-input');
  const categoryModalDashboardNameInput = document.getElementById('modal-category-dashboard-name-input');
  const categoryModalSaveBtn = document.getElementById('modal-save-button');
  const categoryModalCancelBtn = document.getElementById('modal-cancel-button');

  // --- Variáveis de Estado da Página ---
  let editingCategoryId = null;
  let localData = { categories: [] };

  // --- Funções Específicas da Página ---
  async function carregarDados() {
    const response = await apiCall('read_all', 'GET');
    if (response && response.categories) {
      localData = response;
      renderizar();
    }
  }

  function renderizar() {
    linkListContainer.innerHTML = '';
    selectCategory.innerHTML = '<option value="">Selecione uma categoria...</option>';
    if (!localData.categories) localData.categories = [];

    localData.categories.forEach((category) => {
      const categoryBlock = document.createElement('div');
      categoryBlock.className = 'category-block';
      categoryBlock.dataset.categoryId = category.id;

      const categoryHeader = document.createElement('div');
      categoryHeader.className = 'category-header';
      categoryHeader.innerHTML = `
        <h2 title="Arraste para reordenar a categoria"><span class="drag-handle">✥</span><span>${category.name}</span></h2>
        <div>
          <button class="btn btn-sm btn-editar btn-editar-categoria" data-category-id="${category.id}" data-category-name="${category.name}" data-category-dashboard-name="${category.dashboard_name || ''}" title="Editar Nomes da Categoria">Editar</button>
          <button class="btn btn-sm btn-excluir btn-excluir-categoria" data-category-id="${category.id}" title="Excluir Categoria">Excluir</button>
        </div>`;

      const linksContainer = document.createElement('div');
      linksContainer.className = 'links-container';
      linksContainer.dataset.categoryId = category.id;

      if (category.links) {
        category.links.forEach((link) => {
          const linkRow = document.createElement('div');
          linkRow.className = 'grid-row';
          linkRow.dataset.linkId = link.id;
          linkRow.innerHTML = `
            <div class="drag-handle">✥</div>
            <div class="link-info"><strong>${link.text}</strong><br><small>${link.url}</small></div>
            <div class="link-actions">
              <button class="btn btn-sm btn-editar btn-editar-link" data-link-id="${link.id}">Editar</button>
              <button class="btn btn-sm btn-excluir btn-excluir-link" data-link-id="${link.id}">Excluir</button>
            </div>`;
          linksContainer.appendChild(linkRow);
        });
      }

      categoryBlock.appendChild(categoryHeader);
      categoryBlock.appendChild(linksContainer);
      linkListContainer.appendChild(categoryBlock);

      const option = document.createElement('option');
      option.value = category.id;
      option.textContent = category.name;
      selectCategory.appendChild(option);
    });

    addEventListeners();
    initSortable();
  }

  function addEventListeners() {
    document.querySelectorAll('.btn-excluir-link').forEach((btn) => {
      btn.onclick = (e) => {
        const linkId = e.currentTarget.dataset.linkId;
        showConfirmModal('Tem certeza que deseja excluir este link?', async () => {
          const result = await apiCall('delete_link', 'POST', { id: linkId });
          if (result && result.success) carregarDados();
        });
      };
    });

    document.querySelectorAll('.btn-excluir-categoria').forEach((btn) => {
      btn.onclick = (e) => {
        const categoryId = e.currentTarget.dataset.categoryId;
        const message = 'ATENÇÃO: Excluir uma categoria também excluirá TODOS os links dentro dela. Deseja continuar?';
        showConfirmModal(message, async () => {
          const result = await apiCall('delete_category', 'POST', { id: categoryId });
          if (result && result.success) carregarDados();
        });
      };
    });

    document.querySelectorAll('.btn-editar-link').forEach((btn) => {
      btn.onclick = (e) => preencherFormularioParaEdicao(e.currentTarget.dataset.linkId);
    });

    document.querySelectorAll('.btn-editar-categoria').forEach((btn) => {
      btn.onclick = (e) => {
        const el = e.currentTarget;
        openCategoryEditModal(el.dataset.categoryId, el.dataset.categoryName, el.dataset.categoryDashboardName);
      };
    });
  }

  function openCategoryEditModal(id, currentName, currentDashboardName) {
    editingCategoryId = id;
    categoryModalInput.value = currentName;
    categoryModalDashboardNameInput.value = currentDashboardName;
    categoryModal.style.display = 'flex';
    categoryModalInput.focus();
  }

  function closeCategoryEditModal() {
    categoryModal.style.display = 'none';
    editingCategoryId = null;
    categoryModalInput.value = '';
    categoryModalDashboardNameInput.value = '';
  }

  async function saveCategoryName() {
    if (!editingCategoryId) return;
    const newName = categoryModalInput.value.trim();
    const newDashboardName = categoryModalDashboardNameInput.value.trim();
    if (newName) {
      const result = await apiCall('update_category', 'POST', { id: editingCategoryId, name: newName, dashboard_name: newDashboardName });
      if (result && result.success) {
        closeCategoryEditModal();
        carregarDados();
      }
    }
  }

  function initSortable() {
    new Sortable(linkListContainer, { animation: 150, handle: '.category-header', ghostClass: 'sortable-ghost', onEnd: updateLocalDataOrder });
    document.querySelectorAll('.links-container').forEach((container) => {
      new Sortable(container, { animation: 150, group: 'links', handle: '.drag-handle', ghostClass: 'sortable-ghost', onEnd: updateLocalDataOrder });
    });
  }

  function updateLocalDataOrder() {
    // 1. Para segurança, criamos um mapa de todos os links existentes pelo ID.
    // Isso nos dá uma fonte única e confiável para os dados de cada link.
    const allLinksMap = new Map();
    localData.categories.forEach((category) => {
      (category.links || []).forEach((link) => {
        allLinksMap.set(link.id.toString(), link);
      });
    });

    // 2. Construímos uma estrutura de categorias completamente nova baseada na ordem do DOM.
    const newCategories = [];
    document.querySelectorAll('.category-block').forEach((categoryElement) => {
      const categoryId = categoryElement.dataset.categoryId;
      const originalCategory = localData.categories.find((c) => c.id.toString() === categoryId);

      if (originalCategory) {
        // Criamos uma cópia da categoria com uma lista de links vazia.
        const updatedCategory = { ...originalCategory, links: [] };

        // Populamos a lista de links com os dados corretos do nosso mapa.
        categoryElement.querySelectorAll('.grid-row').forEach((linkElement) => {
          const linkId = linkElement.dataset.linkId;
          const linkData = allLinksMap.get(linkId);
          if (linkData) {
            updatedCategory.links.push(linkData);
          }
        });
        newCategories.push(updatedCategory);
      }
    });

    // 3. Substituímos os dados antigos pela nova estrutura, pronta para ser salva.
    localData.categories = newCategories;
  }

  function preencherFormularioParaEdicao(linkId) {
    let linkToEdit, categoryOfLink;
    localData.categories.forEach((cat) => {
      const found = (cat.links || []).find((link) => link.id == linkId);
      if (found) {
        linkToEdit = found;
        categoryOfLink = cat;
      }
    });
    if (!linkToEdit) return;

    form.classList.add('editing');
    formTitle.textContent = 'Editar Link';
    btnAddUpdate.textContent = 'Salvar Alteração';
    btnCancelEdit.style.display = 'inline-block';

    inputLinkId.value = linkToEdit.id;
    inputLinkText.value = linkToEdit.text;
    inputDashboardText.value = linkToEdit.dashboard_text;
    inputLinkUrl.value = linkToEdit.url;
    inputLinkIcon.value = linkToEdit.icon;
    selectCategory.value = categoryOfLink.id;
    checkShowOnDashboard.checked = !!parseInt(linkToEdit.showOnDashboard);
    checkOpenInNewTab.checked = !!parseInt(linkToEdit.openInNewTab);
    document.querySelector(`input[name="visibilidade"][value="${linkToEdit.visibilidade}"]`).checked = true;
  }

  function resetarFormulario() {
    form.reset();
    form.classList.remove('editing');
    inputLinkId.value = '';
    formTitle.textContent = 'Adicionar Novo Link';
    btnAddUpdate.textContent = 'Adicionar Link';
    btnCancelEdit.style.display = 'none';
    selectCategory.value = '';
  }

  // --- Inicialização e Eventos da Página ---

  btnCancelEdit.addEventListener('click', resetarFormulario);

  btnAddUpdate.addEventListener('click', async () => {
    const isEditing = !!inputLinkId.value;
    const action = isEditing ? 'update_link' : 'add_link';
    let categoria_id = selectCategory.value;
    let new_category_name = inputNewCategory.value.trim();

    if (!categoria_id && !new_category_name) {
      showFeedbackModal('Por favor, selecione uma categoria existente ou crie uma nova.', 'Atenção');
      return;
    }

    const linkData = {
      id: inputLinkId.value,
      text: inputLinkText.value,
      url: inputLinkUrl.value,
      dashboard_text: inputDashboardText.value,
      icon: inputLinkIcon.value,
      showOnDashboard: checkShowOnDashboard.checked,
      openInNewTab: checkOpenInNewTab.checked,
      visibilidade: document.querySelector('input[name="visibilidade"]:checked').value,
      categoria_id: new_category_name ? 'new' : categoria_id,
      new_category_name: new_category_name,
      new_category_dashboard_name: inputNewCategoryDashboard.value.trim(),
    };

    const result = await apiCall(action, 'POST', linkData);
    if (result && result.success) {
      resetarFormulario();
      carregarDados();
    }
  });

  btnSalvarAlteracoes.addEventListener('click', async () => {
    updateLocalDataOrder();
    const result = await apiCall('save_order', 'POST', localData);
    if (result && result.success) {
      showFeedbackModal(result.message);
    }
  });

  categoryModalSaveBtn.addEventListener('click', saveCategoryName);
  categoryModalCancelBtn.addEventListener('click', closeCategoryEditModal);
  categoryModal.addEventListener('click', (e) => {
    if (e.target === categoryModal) closeCategoryEditModal();
  });
  categoryModalInput.addEventListener('keyup', (e) => {
    if (e.key === 'Enter') saveCategoryName();
  });
  categoryModalDashboardNameInput.addEventListener('keyup', (e) => {
    if (e.key === 'Enter') saveCategoryName();
  });

  // Carga inicial dos dados
  carregarDados();
});
