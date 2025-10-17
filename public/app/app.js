const state = {
  token: localStorage.getItem('gil_token') ?? null,
  lists: [],
  selectedListId: null,
  games: [],
  templatesByGame: new Map(),
};

const els = {
  authStatus: document.querySelector('#auth-status'),
  gamesCard: document.querySelector('#games-card'),
  listsCard: document.querySelector('#lists-card'),
  listsContainer: document.querySelector('#lists'),
  listDetail: document.querySelector('#list-detail'),
  listTitle: document.querySelector('#list-title'),
  listDescription: document.querySelector('#list-description'),
  itemsList: document.querySelector('#items'),
  itemsHint: document.querySelector('#items-hint'),
  templateSelect: document.querySelector('#template-select'),
  sharePanel: document.querySelector('#share-panel'),
  shareStatus: document.querySelector('#share-status'),
  shareLinkWrapper: document.querySelector('#share-link'),
  shareLinkInput: document.querySelector('#share-link input'),
  shareCopy: document.querySelector('#share-copy'),
  shareCreate: document.querySelector('#share-create'),
  shareRotate: document.querySelector('#share-rotate'),
  shareRevoke: document.querySelector('#share-revoke'),
  createListForm: document.querySelector('#create-list-form'),
  registerForm: document.querySelector('#register-form'),
  loginForm: document.querySelector('#login-form'),
  manualAddForm: document.querySelector('#manual-add-form'),
  templateAddForm: document.querySelector('#template-add-form'),
  gameSelect: document.querySelector('#game-select'),
  sharedViewCard: document.querySelector('#shared-view-card'),
  sharedView: document.querySelector('#shared-view'),
};

async function apiFetch(path, { method = 'GET', body, auth = true } = {}) {
  const headers = { 'Content-Type': 'application/json' };

  if (auth) {
    if (!state.token) {
      throw new Error('Authentication required');
    }

    headers.Authorization = `Bearer ${state.token}`;
  }

  const response = await fetch(path, {
    method,
    headers,
    body: body ? JSON.stringify(body) : undefined,
  });

  if (response.status === 204) {
    return null;
  }

  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    const message = data.detail ?? data.message ?? 'Request failed';
    throw new Error(message);
  }

  return data;
}

function setAuthState(token, email) {
  if (token) {
    state.token = token;
    localStorage.setItem('gil_token', token);
    els.authStatus.textContent = `Signed in as ${email ?? 'account'}.`;
    els.gamesCard.hidden = false;
    els.listsCard.hidden = false;
  } else {
    state.token = null;
    localStorage.removeItem('gil_token');
    els.authStatus.textContent = 'Not authenticated.';
    els.gamesCard.hidden = true;
    els.listsCard.hidden = true;
    state.lists = [];
    renderLists();
    els.listDetail.hidden = true;
  }
}

async function handleRegister(event) {
  event.preventDefault();
  const form = event.currentTarget;
  const body = Object.fromEntries(new FormData(form).entries());

  try {
    const { accessToken } = await apiFetch('/v1/auth/register', {
      method: 'POST',
      body,
      auth: false,
    });

    setAuthState(accessToken, body.email);
    await refreshLists();
  } catch (error) {
    alert(error.message);
  }
}

async function handleLogin(event) {
  event.preventDefault();
  const form = event.currentTarget;
  const body = Object.fromEntries(new FormData(form).entries());

  try {
    const { accessToken } = await apiFetch('/v1/auth/login', {
      method: 'POST',
      body,
      auth: false,
    });

    setAuthState(accessToken, body.email);
    await refreshLists();
  } catch (error) {
    alert(error.message);
  }
}

async function refreshLists() {
  if (!state.token) {
    return;
  }

  try {
    const data = await apiFetch('/v1/lists');
    state.lists = data.data ?? [];
    renderLists();

    if (state.selectedListId) {
      const stillExists = state.lists.some((list) => list.id === state.selectedListId);

      if (!stillExists) {
        state.selectedListId = null;
        els.listDetail.hidden = true;
      }
    }
  } catch (error) {
    console.error(error);
    alert(`Unable to load lists: ${error.message}`);
  }
}

function renderLists() {
  els.listsContainer.innerHTML = '';

  if (state.lists.length === 0) {
    const empty = document.createElement('li');
    empty.textContent = 'No lists yet. Create one to get started.';
    empty.className = 'muted';
    els.listsContainer.appendChild(empty);
    return;
  }

  state.lists.forEach((list) => {
    const li = document.createElement('li');
    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = `${list.name} — ${list.game.name}`;
    if (state.selectedListId === list.id) {
      button.classList.add('active');
    }
    button.addEventListener('click', () => selectList(list.id));
    li.appendChild(button);
    els.listsContainer.appendChild(li);
  });
}

async function selectList(listId) {
  state.selectedListId = listId;
  renderLists();

  try {
    const detail = await apiFetch(`/v1/lists/${listId}`);
    renderListDetail(detail);
    await loadShareStatus(listId);
    await ensureTemplatesLoaded(detail.game.id);
  } catch (error) {
    alert(`Unable to load list: ${error.message}`);
  }
}

function renderListDetail(detail) {
  els.listDetail.hidden = false;
  els.listTitle.textContent = `${detail.name} — ${detail.game.name}`;
  els.listDescription.textContent = detail.description ?? 'No description provided.';

  const items = detail.items ?? [];
  els.itemsList.innerHTML = '';

  if (items.length === 0) {
    els.itemsHint.hidden = false;
  } else {
    els.itemsHint.hidden = true;
    items.forEach((item) => {
      const li = document.createElement('li');
      const title = document.createElement('h5');
      title.textContent = item.name;
      li.appendChild(title);

      const type = document.createElement('p');
      type.className = 'muted';
      type.textContent = `Type: ${item.storageType}`;
      li.appendChild(type);

      if (item.description) {
        const desc = document.createElement('p');
        desc.textContent = item.description;
        li.appendChild(desc);
      }

      if (item.tags && item.tags.length > 0) {
        const tagList = document.createElement('div');
        tagList.className = 'tag-list';
        item.tags.forEach((tag) => {
          const badge = document.createElement('span');
          badge.className = 'tag';
          badge.textContent = tag.name;
          tagList.appendChild(badge);
        });
        li.appendChild(tagList);
      }

      els.itemsList.appendChild(li);
    });
  }
}

async function ensureTemplatesLoaded(gameId) {
  if (state.templatesByGame.has(gameId)) {
    populateTemplateSelect(state.templatesByGame.get(gameId));
    return;
  }

  try {
    const data = await apiFetch(`/v1/games/${gameId}/item-templates`, { auth: false });
    const templates = data.templates ?? [];
    state.templatesByGame.set(gameId, templates);
    populateTemplateSelect(templates);
  } catch (error) {
    console.error('Failed to load templates', error);
    populateTemplateSelect([]);
  }
}

function populateTemplateSelect(templates) {
  els.templateSelect.innerHTML = '';
  if (templates.length === 0) {
    const option = document.createElement('option');
    option.value = '';
    option.textContent = 'No templates available';
    els.templateSelect.appendChild(option);
    els.templateSelect.disabled = true;
  } else {
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Select template…';
    els.templateSelect.appendChild(placeholder);
    templates.forEach((template) => {
      const option = document.createElement('option');
      option.value = template.id;
      option.textContent = template.name;
      els.templateSelect.appendChild(option);
    });
    els.templateSelect.disabled = false;
  }
}

async function loadShareStatus(listId) {
  try {
    const status = await apiFetch(`/v1/lists/${listId}/share`);
    updateSharePanel(status);
  } catch (error) {
    console.error('Failed to load share status', error);
    els.shareStatus.textContent = `Unable to load share status: ${error.message}`;
  }
}

function updateSharePanel(status) {
  const isActive = Boolean(status.active);

  if (!isActive) {
    els.shareStatus.textContent = 'This list is private.';
    els.shareLinkWrapper.hidden = true;
    els.shareCreate.disabled = false;
    els.shareRotate.disabled = true;
    els.shareRevoke.disabled = true;
    return;
  }

  const token = status.token;
  const uiLink = `${window.location.origin}/app/index.html#shared=${token}`;

  els.shareStatus.textContent = 'Sharing is active. Anyone with the link can view this list.';
  els.shareLinkWrapper.hidden = false;
  els.shareLinkInput.value = uiLink;
  els.shareCreate.disabled = true;
  els.shareRotate.disabled = false;
  els.shareRevoke.disabled = false;
}

async function handleCreateList(event) {
  event.preventDefault();
  if (!state.token) {
    return;
  }

  const formData = new FormData(event.currentTarget);
  const payload = Object.fromEntries(formData.entries());
  payload.isPublished = formData.get('isPublished') === 'on';

  try {
    await apiFetch('/v1/lists', { method: 'POST', body: payload });
    event.currentTarget.reset();
    await refreshLists();
  } catch (error) {
    alert(`Unable to create list: ${error.message}`);
  }
}

async function handleManualAdd(event) {
  event.preventDefault();
  if (!state.selectedListId) {
    return;
  }

  const payload = Object.fromEntries(new FormData(event.currentTarget).entries());

  try {
    await apiFetch(`/v1/lists/${state.selectedListId}/items`, { method: 'POST', body: payload });
    alert('Item proposal submitted for review.');
    event.currentTarget.reset();
  } catch (error) {
    alert(`Unable to add item: ${error.message}`);
  }
}

async function handleTemplateAdd(event) {
  event.preventDefault();
  if (!state.selectedListId) {
    return;
  }

  const formData = new FormData(event.currentTarget);
  const templateId = formData.get('templateId');

  if (!templateId) {
    alert('Please pick a template first.');
    return;
  }

  try {
    await apiFetch(`/v1/lists/${state.selectedListId}/items`, {
      method: 'POST',
      body: { templateId },
    });
    alert('Template proposal submitted for review.');
    event.currentTarget.reset();
  } catch (error) {
    alert(`Unable to add template: ${error.message}`);
  }
}

async function createShare(rotate = false) {
  if (!state.selectedListId) {
    return;
  }

  try {
    const status = await apiFetch(`/v1/lists/${state.selectedListId}/share`, {
      method: 'POST',
      body: rotate ? { rotate: true } : undefined,
    });
    updateSharePanel(status);
  } catch (error) {
    alert(`Unable to update share: ${error.message}`);
  }
}

async function revokeShare() {
  if (!state.selectedListId) {
    return;
  }

  try {
    await apiFetch(`/v1/lists/${state.selectedListId}/share`, { method: 'DELETE' });
    updateSharePanel({ active: false });
  } catch (error) {
    alert(`Unable to revoke share: ${error.message}`);
  }
}

async function copyShareLink() {
  const value = els.shareLinkInput.value;
  if (!value) {
    return;
  }

  try {
    await navigator.clipboard.writeText(value);
    els.shareCopy.textContent = 'Copied!';
    setTimeout(() => {
      els.shareCopy.textContent = 'Copy';
    }, 2000);
  } catch (error) {
    console.error('Clipboard copy failed', error);
  }
}

async function loadGames() {
  try {
    const data = await apiFetch('/v1/games', { auth: false });
    state.games = data.games ?? [];
    els.gameSelect.innerHTML = '';
    state.games.forEach((game) => {
      const option = document.createElement('option');
      option.value = game.id;
      option.textContent = game.name;
      els.gameSelect.appendChild(option);
    });
  } catch (error) {
    console.error('Failed to load games', error);
  }
}

async function loadSharedViewFromHash() {
  const hash = window.location.hash;
  if (!hash.startsWith('#shared=')) {
    els.sharedViewCard.hidden = true;
    els.sharedView.innerHTML = '';
    return;
  }

  const token = hash.replace('#shared=', '').trim();

  if (!token) {
    return;
  }

  try {
    const detail = await apiFetch(`/v1/shared/${token}`, { auth: false });
    renderSharedView(detail);
  } catch (error) {
    els.sharedViewCard.hidden = false;
    els.sharedView.innerHTML = `<p class="muted">Unable to load shared list: ${error.message}</p>`;
  }
}

function renderSharedView(detail) {
  els.sharedViewCard.hidden = false;
  els.sharedView.innerHTML = '';

  const heading = document.createElement('h3');
  heading.textContent = `${detail.name} — ${detail.game.name}`;
  els.sharedView.appendChild(heading);

  const description = document.createElement('p');
  description.className = 'muted';
  description.textContent = detail.description ?? 'No description provided.';
  els.sharedView.appendChild(description);

  if (Array.isArray(detail.items) && detail.items.length > 0) {
    const list = document.createElement('ul');
    list.className = 'items-list';
    detail.items.forEach((item) => {
      const li = document.createElement('li');
      const title = document.createElement('h5');
      title.textContent = item.name;
      li.appendChild(title);
      if (item.description) {
        const desc = document.createElement('p');
        desc.textContent = item.description;
        li.appendChild(desc);
      }
      const type = document.createElement('p');
      type.className = 'muted';
      type.textContent = `Type: ${item.storageType}`;
      li.appendChild(type);
      list.appendChild(li);
    });
    els.sharedView.appendChild(list);
  } else {
    const empty = document.createElement('p');
    empty.className = 'muted';
    empty.textContent = 'No items have been approved yet.';
    els.sharedView.appendChild(empty);
  }
}

function bindEvents() {
  els.registerForm.addEventListener('submit', handleRegister);
  els.loginForm.addEventListener('submit', handleLogin);
  els.createListForm.addEventListener('submit', handleCreateList);
  els.manualAddForm.addEventListener('submit', handleManualAdd);
  els.templateAddForm.addEventListener('submit', handleTemplateAdd);
  els.shareCreate.addEventListener('click', () => createShare(false));
  els.shareRotate.addEventListener('click', () => createShare(true));
  els.shareRevoke.addEventListener('click', revokeShare);
  els.shareCopy.addEventListener('click', copyShareLink);
  window.addEventListener('hashchange', loadSharedViewFromHash);
}

async function init() {
  bindEvents();
  await loadGames();
  await loadSharedViewFromHash();

  if (state.token) {
    setAuthState(state.token);
    await refreshLists();
  }
}

init();
