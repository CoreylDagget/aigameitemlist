const state = {
  token: localStorage.getItem('gil_token') ?? null,
  lists: [],
  selectedListId: null,
  games: [],
  templatesByGame: new Map(),
  activeListDetail: null,
  itemFilters: { search: '', tagId: '' },
};

const DEFAULT_TAG_COLOR = '#38bdf8';
const tagNameCollator = new Intl.Collator(undefined, { sensitivity: 'base' });

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
  tagsList: document.querySelector('#tags'),
  tagsHint: document.querySelector('#tags-hint'),
  tagAddForm: document.querySelector('#tag-add-form'),
  tagColorToggle: document.querySelector('#tag-color-toggle'),
  tagColorInput: document.querySelector('#tag-color-input'),
  itemsFilterSearch: document.querySelector('#items-filter-search'),
  itemsFilterTag: document.querySelector('#items-filter-tag'),
  itemsFiltersClear: document.querySelector('#items-filters-clear'),
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

function hexToRgba(hex, alpha) {
  if (typeof hex !== 'string') {
    return null;
  }

  const match = /^#?([0-9a-f]{6})$/i.exec(hex.trim());

  if (!match) {
    return null;
  }

  const value = match[1];
  const r = parseInt(value.slice(0, 2), 16);
  const g = parseInt(value.slice(2, 4), 16);
  const b = parseInt(value.slice(4, 6), 16);

  return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function applyTagColor(element, color) {
  if (!element || !color) {
    return;
  }

  const background = hexToRgba(color, 0.18);
  const border = hexToRgba(color, 0.45);

  if (background) {
    element.style.backgroundColor = background;
  }

  if (border) {
    element.style.borderColor = border;
  }

  element.style.color = color;
  element.dataset.color = color;
}

function createTagBadge(tag) {
  const badge = document.createElement('span');
  badge.className = 'tag';
  badge.textContent = tag.name;

  if (tag.color) {
    applyTagColor(badge, tag.color);
  }

  return badge;
}

function appendTagList(target, tags) {
  if (!target || !Array.isArray(tags) || tags.length === 0) {
    return null;
  }

  const tagList = document.createElement('div');
  tagList.className = 'tag-list';

  const sortedTags = [...tags].sort((a, b) =>
    tagNameCollator.compare(a.name ?? '', b.name ?? '')
  );

  sortedTags.forEach((tag) => {
    if (!tag || !tag.name) {
      return;
    }

    tagList.appendChild(createTagBadge(tag));
  });

  if (tagList.childElementCount === 0) {
    return null;
  }

  target.appendChild(tagList);
  return tagList;
}

function formatTimestamp(value) {
  if (!value) {
    return null;
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return null;
  }

  return date.toLocaleString();
}

function describeStorageType(value) {
  switch (value) {
    case 'boolean':
      return 'Yes / No';
    case 'count':
      return 'Count';
    case 'text':
      return 'Text';
    default:
      return 'unknown';
  }
}

function populateTagFilter(tags = []) {
  if (!els.itemsFilterTag) {
    return;
  }

  els.itemsFilterTag.innerHTML = '';

  const placeholder = document.createElement('option');
  placeholder.value = '';
  placeholder.textContent = tags.length > 0 ? 'All tags' : 'No tags available';
  els.itemsFilterTag.appendChild(placeholder);
  els.itemsFilterTag.disabled = tags.length === 0;

  const sortedTags = [...tags].sort((a, b) =>
    tagNameCollator.compare(a.name ?? '', b.name ?? '')
  );

  sortedTags.forEach((tag) => {
    if (!tag || !tag.id) {
      return;
    }

    const option = document.createElement('option');
    option.value = tag.id;
    option.textContent = tag.name;
    els.itemsFilterTag.appendChild(option);
  });
}

function updateItemsFiltersUI() {
  if (els.itemsFilterSearch) {
    els.itemsFilterSearch.value = state.itemFilters.search ?? '';
  }

  if (els.itemsFilterTag) {
    const desiredValue = state.itemFilters.tagId ?? '';
    const optionValues = Array.from(els.itemsFilterTag.options, (option) => option.value);

    if (!optionValues.includes(desiredValue)) {
      state.itemFilters.tagId = '';
    }

    els.itemsFilterTag.value = state.itemFilters.tagId ?? '';
  }

  if (els.itemsFiltersClear) {
    const hasFilters = Boolean(state.itemFilters.search) || Boolean(state.itemFilters.tagId);
    els.itemsFiltersClear.disabled = !hasFilters;
  }
}

function renderTags(tags = []) {
  if (!els.tagsList || !els.tagsHint) {
    return;
  }

  els.tagsList.innerHTML = '';

  const hasTags = Array.isArray(tags) && tags.length > 0;
  els.tagsHint.hidden = hasTags;
  els.tagsList.hidden = !hasTags;

  if (!hasTags) {
    return;
  }

  const sortedTags = [...tags].sort((a, b) =>
    tagNameCollator.compare(a.name ?? '', b.name ?? '')
  );

  sortedTags.forEach((tag) => {
    if (!tag || !tag.name) {
      return;
    }

    const item = document.createElement('li');
    item.appendChild(createTagBadge(tag));
    els.tagsList.appendChild(item);
  });
}

function renderItems() {
  if (!els.itemsList || !els.itemsHint) {
    return;
  }

  const detail = state.activeListDetail;

  if (!detail) {
    els.itemsList.innerHTML = '';
    els.itemsHint.hidden = false;
    els.itemsHint.textContent = 'Select a list to view its items.';
    return;
  }

  const allItems = Array.isArray(detail.items) ? detail.items : [];
  let filtered = [...allItems];

  if (state.itemFilters.tagId) {
    filtered = filtered.filter((item) =>
      Array.isArray(item.tags) && item.tags.some((tag) => tag.id === state.itemFilters.tagId)
    );
  }

  if (state.itemFilters.search) {
    const needle = state.itemFilters.search.toLowerCase();

    filtered = filtered.filter((item) => {
      const haystacks = [
        item.name ?? '',
        item.description ?? '',
        ...(Array.isArray(item.tags) ? item.tags.map((tag) => tag.name ?? '') : []),
      ];

      return haystacks.some((value) => value.toLowerCase().includes(needle));
    });
  }

  els.itemsList.innerHTML = '';

  if (allItems.length === 0) {
    els.itemsHint.hidden = false;
    els.itemsHint.textContent = 'No items yet. Approved items will show here once available.';
    return;
  }

  if (filtered.length === 0) {
    els.itemsHint.hidden = false;
    els.itemsHint.textContent = 'No items match the current filters. Try adjusting or clearing them.';
    return;
  }

  els.itemsHint.hidden = true;

  filtered.forEach((item) => {
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
    const storageLabel = describeStorageType(item.storageType);
    type.textContent = `Type: ${storageLabel}`;
    li.appendChild(type);

    appendTagList(li, Array.isArray(item.tags) ? item.tags : []);

    els.itemsList.appendChild(li);
  });
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
    state.activeListDetail = null;
    state.itemFilters = { search: '', tagId: '' };
    state.templatesByGame.clear();
    renderLists();
    els.listDetail.hidden = true;
    renderTags([]);
    populateTagFilter([]);
    populateTemplateSelect([]);
    updateItemsFiltersUI();
    renderItems();
    if (els.tagAddForm) {
      els.tagAddForm.reset();
    }
    if (els.tagColorToggle) {
      els.tagColorToggle.checked = false;
    }
    handleTagColorToggle();
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
        state.activeListDetail = null;
        state.itemFilters = { search: '', tagId: '' };
        renderTags([]);
        populateTagFilter([]);
        updateItemsFiltersUI();
        renderItems();
      } else {
        await loadListDetail(state.selectedListId, { preserveFilters: true });
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

async function loadListDetail(listId, { preserveFilters = false } = {}) {
  if (!listId) {
    return;
  }

  try {
    const detail = await apiFetch(`/v1/lists/${listId}`);
    renderListDetail(detail, { preserveFilters });
    await loadShareStatus(listId);
    await ensureTemplatesLoaded(detail.game.id);
  } catch (error) {
    console.error('Unable to load list', error);
    alert(`Unable to load list: ${error.message}`);
  }
}

async function selectList(listId) {
  state.selectedListId = listId;
  renderLists();
  await loadListDetail(listId);
}

function renderListDetail(detail, { preserveFilters = false } = {}) {
  state.activeListDetail = detail;
  els.listDetail.hidden = false;
  els.listTitle.textContent = `${detail.name} — ${detail.game.name}`;
  els.listDescription.textContent = detail.description ?? 'No description provided.';

  if (els.shareStatus) {
    els.shareStatus.textContent = 'Loading share status…';
  }

  if (els.shareLinkWrapper) {
    els.shareLinkWrapper.hidden = true;
  }

  if (els.shareLinkInput) {
    els.shareLinkInput.value = '';
  }

  if (els.shareCopy) {
    els.shareCopy.disabled = true;
    els.shareCopy.textContent = 'Copy';
  }

  if (els.shareCreate) {
    els.shareCreate.disabled = true;
  }

  if (els.shareRotate) {
    els.shareRotate.disabled = true;
  }

  if (els.shareRevoke) {
    els.shareRevoke.disabled = true;
  }

  if (!preserveFilters) {
    if (els.tagAddForm) {
      els.tagAddForm.reset();
    }

    if (els.tagColorToggle) {
      els.tagColorToggle.checked = false;
    }

    if (els.manualAddForm) {
      els.manualAddForm.reset();
    }

    if (els.templateAddForm) {
      els.templateAddForm.reset();
    }
  }

  handleTagColorToggle();

  const tags = Array.isArray(detail.tags) ? detail.tags : [];
  renderTags(tags);
  populateTagFilter(tags);

  const nextFilters = preserveFilters ? { ...state.itemFilters } : { search: '', tagId: '' };
  state.itemFilters = nextFilters;
  updateItemsFiltersUI();
  renderItems();
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
    if (els.shareStatus) {
      els.shareStatus.textContent = `Unable to load share status: ${error.message}`;
    }

    if (els.shareLinkWrapper) {
      els.shareLinkWrapper.hidden = true;
    }

    if (els.shareCreate) {
      els.shareCreate.disabled = false;
    }

    if (els.shareRotate) {
      els.shareRotate.disabled = true;
    }

    if (els.shareRevoke) {
      els.shareRevoke.disabled = true;
    }

    if (els.shareCopy) {
      els.shareCopy.disabled = true;
      els.shareCopy.textContent = 'Copy';
    }

    if (els.shareLinkInput) {
      els.shareLinkInput.value = '';
    }
  }
}

function updateSharePanel(status) {
  const isActive = Boolean(status?.active);

  if (!isActive) {
    if (els.shareStatus) {
      els.shareStatus.textContent = 'This list is private.';
    }

    if (els.shareLinkWrapper) {
      els.shareLinkWrapper.hidden = true;
    }

    if (els.shareCreate) {
      els.shareCreate.disabled = false;
    }

    if (els.shareRotate) {
      els.shareRotate.disabled = true;
    }

    if (els.shareRevoke) {
      els.shareRevoke.disabled = true;
    }

    if (els.shareCopy) {
      els.shareCopy.disabled = true;
      els.shareCopy.textContent = 'Copy';
    }

    if (els.shareLinkInput) {
      els.shareLinkInput.value = '';
    }

    return;
  }

  const shareUrl =
    status.shareUrl ??
    (status.token ? `${window.location.origin}/app/index.html#shared=${status.token}` : '');

  const createdAt = formatTimestamp(status.createdAt);
  let message = 'Sharing is active. Anyone with the link can view this list.';

  if (createdAt) {
    message += ` Updated ${createdAt}.`;
  }

  if (els.shareStatus) {
    els.shareStatus.textContent = message;
  }

  if (els.shareLinkWrapper) {
    els.shareLinkWrapper.hidden = !shareUrl;
  }

  if (els.shareLinkInput) {
    els.shareLinkInput.value = shareUrl;
  }

  if (els.shareCreate) {
    els.shareCreate.disabled = true;
  }

  if (els.shareRotate) {
    els.shareRotate.disabled = false;
  }

  if (els.shareRevoke) {
    els.shareRevoke.disabled = false;
  }

  if (els.shareCopy) {
    els.shareCopy.disabled = !shareUrl;
    els.shareCopy.textContent = 'Copy';
  }
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

async function handleTagAdd(event) {
  event.preventDefault();

  if (!state.selectedListId) {
    return;
  }

  const form = event.currentTarget;
  const formData = new FormData(form);
  const name = (formData.get('name') ?? '').toString().trim();

  if (!name) {
    alert('Tag name is required.');
    return;
  }

  const payload = { name };

  if (els.tagColorToggle?.checked) {
    const color = els.tagColorInput?.value;
    if (color) {
      payload.color = color;
    }
  }

  try {
    await apiFetch(`/v1/lists/${state.selectedListId}/tags`, { method: 'POST', body: payload });
    alert('Tag proposal submitted for review.');
    form.reset();

    if (els.tagColorToggle) {
      els.tagColorToggle.checked = false;
    }

    if (els.tagColorInput) {
      els.tagColorInput.value = DEFAULT_TAG_COLOR;
    }

    handleTagColorToggle();
  } catch (error) {
    alert(`Unable to add tag: ${error.message}`);
  }
}

function handleTagColorToggle() {
  if (!els.tagColorToggle || !els.tagColorInput) {
    return;
  }

  const enabled = els.tagColorToggle.checked;
  els.tagColorInput.disabled = !enabled;

  if (!enabled) {
    els.tagColorInput.value = DEFAULT_TAG_COLOR;
  }
}

function handleItemsFilterSearch(event) {
  state.itemFilters = {
    ...state.itemFilters,
    search: event.target.value.trim(),
  };
  updateItemsFiltersUI();
  renderItems();
}

function handleItemsFilterTag(event) {
  state.itemFilters = {
    ...state.itemFilters,
    tagId: event.target.value,
  };
  updateItemsFiltersUI();
  renderItems();
}

function clearItemFilters(event) {
  event.preventDefault();
  state.itemFilters = { search: '', tagId: '' };
  updateItemsFiltersUI();
  renderItems();

  if (els.itemsFilterSearch) {
    els.itemsFilterSearch.focus();
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

  if (Array.isArray(detail.tags) && detail.tags.length > 0) {
    const tagsSection = document.createElement('div');
    tagsSection.className = 'shared-tags';
    const tagsHeading = document.createElement('h5');
    tagsHeading.textContent = 'Tags';
    tagsSection.appendChild(tagsHeading);
    appendTagList(tagsSection, detail.tags);
    els.sharedView.appendChild(tagsSection);
  }

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
      const storageLabel = describeStorageType(item.storageType);
      type.textContent = `Type: ${storageLabel}`;
      li.appendChild(type);
      appendTagList(li, Array.isArray(item.tags) ? item.tags : []);
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
  els.tagAddForm.addEventListener('submit', handleTagAdd);
  els.tagColorToggle.addEventListener('change', handleTagColorToggle);
  els.itemsFilterSearch.addEventListener('input', handleItemsFilterSearch);
  els.itemsFilterTag.addEventListener('change', handleItemsFilterTag);
  els.itemsFiltersClear.addEventListener('click', clearItemFilters);
  els.shareCreate.addEventListener('click', () => createShare(false));
  els.shareRotate.addEventListener('click', () => createShare(true));
  els.shareRevoke.addEventListener('click', revokeShare);
  els.shareCopy.addEventListener('click', copyShareLink);
  window.addEventListener('hashchange', loadSharedViewFromHash);
}

async function init() {
  bindEvents();
  handleTagColorToggle();
  renderTags([]);
  populateTagFilter([]);
  updateItemsFiltersUI();
  renderItems();
  await loadGames();
  await loadSharedViewFromHash();

  if (state.token) {
    setAuthState(state.token);
    await refreshLists();
  }
}

init();
