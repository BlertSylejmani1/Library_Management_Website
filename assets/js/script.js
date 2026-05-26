document.addEventListener('DOMContentLoaded', () => {
  const body = document.body;
  const root = document.documentElement;
  const cookieDays = 30;
  const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
  let pendingBookDeleteId = null;

  const setCookie = (name, value, days = cookieDays) => {
    const expires = new Date(Date.now() + days * 86400000).toUTCString();
    document.cookie = `${name}=${value}; expires=${expires}; path=/`;
  };

  const syncDarkClassTargets = (dark) => {
    ['.app-shell', '.app-main', '.sidebar', '.topnav', '.dashboard', '.student-dash', '.books-page', '.loans-page', '.users-page', '.profile-page'].forEach((selector) => {
      document.querySelectorAll(selector).forEach((element) => {
        element.classList.toggle('dark', dark);
      });
    });
  };

  const createToast = (message, type = 'success') => {
    if (!message) return;
    const toast = document.createElement('div');
    toast.className = `page-alert alert-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2800);
  };

  const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  }[char]));

  const formatLoanDays = (dueAt, status) => {
    if (status === 'returned') return '<span class="days-neutral">-</span>';
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const due = new Date(`${dueAt}T00:00:00`);
    const days = Math.round((due - today) / 86400000);
    return days >= 0
      ? `<span class="days-ok">${days}d left</span>`
      : `<span class="days-overdue">${Math.abs(days)}d late</span>`;
  };

  const adjustLoanCount = (status, amount) => {
    const node = document.querySelector(`[data-loan-count="${status}"]`);
    if (!node) return;
    node.textContent = String(Math.max(0, Number(node.textContent || 0) + amount));
  };

  const formatRequestDate = (value) => new Date(value ? value.replace(' ', 'T') : Date.now()).toLocaleDateString('en-US', {
    month: 'short',
    day: '2-digit',
    year: 'numeric',
  });

  const addStudentRequestItem = (request) => {
    const list = document.querySelector('[data-student-request-list]');
    if (!list) return;
    document.querySelector('[data-student-request-empty]')?.remove();

    const item = document.createElement('div');
    item.className = 'request-item request-item-new';
    item.innerHTML = `
      <div>
        <strong>${escapeHtml(request.book_title || 'Selected book')}</strong>
        <span>${escapeHtml(formatRequestDate(request.requested_at))}</span>
      </div>
      <span class="status-pill status-${escapeHtml(request.status || 'pending')}">${escapeHtml((request.status || 'pending').replace(/^./, (char) => char.toUpperCase()))}</span>
    `;
    list.prepend(item);
  };

  const renderStudentRequests = (requests) => {
    const list = document.querySelector('[data-student-request-list]');
    if (!list) return;

    const items = Array.isArray(requests) ? requests : [];
    if (!items.length) {
      list.innerHTML = '<div class="cat-empty" data-student-request-empty>No book requests yet.</div>';
      return;
    }

    list.innerHTML = items.slice(0, 8).map((request, index) => `
      <div class="request-item ${index === 0 ? 'request-item-new' : ''}">
        <div>
          <strong>${escapeHtml(request.book_title || 'Selected book')}</strong>
          <span>${escapeHtml(formatRequestDate(request.requested_at))}</span>
        </div>
        <span class="status-pill status-${escapeHtml(request.status || 'pending')}">${escapeHtml((request.status || 'pending').replace(/^./, (char) => char.toUpperCase()))}</span>
      </div>
    `).join('');
  };

  const setAdminRequestsEmptyState = () => {
    const list = document.querySelector('[data-admin-request-list]');
    if (!list || list.querySelector('[data-request-row]') || list.querySelector('[data-admin-request-empty]')) return;

    const empty = document.createElement('div');
    empty.className = 'empty-row';
    empty.setAttribute('data-admin-request-empty', '');
    empty.textContent = 'No pending requests.';
    list.appendChild(empty);
  };

  const openModal = (id) => {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('modal-hidden');
    document.body.classList.add('modal-open');
  };

  const closeModal = (id) => {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('modal-hidden');
    document.body.classList.remove('modal-open');
  };

  const applyTheme = (theme) => {
    const dark = theme === 'dark';
    body.classList.toggle('dark', dark);
    root.setAttribute('data-theme', theme);
    syncDarkClassTargets(dark);
    setCookie('ath_theme', theme);
  };

  syncDarkClassTargets(body.classList.contains('dark'));

  document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      applyTheme(body.classList.contains('dark') ? 'light' : 'dark');
    });
  });

  const sidebar = document.getElementById('appSidebar');
  const appMain = document.getElementById('appMain');
  document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      if (!sidebar || !appMain) return;
      const collapsed = sidebar.classList.toggle('collapsed');
      appMain.classList.toggle('sidebar-collapsed', collapsed);
      setCookie('ath_sidebar', collapsed ? 'collapsed' : 'expanded');
    });
  });

  const closeAllDropdowns = () => {
    document.querySelectorAll('[data-dropdown].open').forEach((dropdown) => dropdown.classList.remove('open'));
  };

  document.querySelectorAll('[data-dropdown-toggle]').forEach((button) => {
    button.addEventListener('click', (event) => {
      event.stopPropagation();
      const target = button.getAttribute('data-dropdown-toggle');
      const dropdown = document.querySelector(`[data-dropdown="${target}"]`);
      if (!dropdown) return;
      const isOpen = dropdown.classList.contains('open');
      closeAllDropdowns();
      dropdown.classList.toggle('open', !isOpen);
    });
  });

  document.addEventListener('click', (event) => {
    if (!event.target.closest('.topnav-dropdown-wrap')) {
      closeAllDropdowns();
    }
  });

  document.querySelectorAll('[data-modal-open]').forEach((button) => {
    button.addEventListener('click', () => {
      const modalId = button.getAttribute('data-modal-open');
      openModal(modalId);

      if (modalId === 'bookEditorModal') {
        const form = document.querySelector('[data-book-form]');
        const title = document.querySelector('[data-book-modal-title]');
        const actionInput = document.querySelector('[data-book-form-action]');
        const idInput = document.querySelector('[data-book-id]');
        if (!form || !title || !actionInput || !idInput) return;
        form.reset();
        title.textContent = 'Add New Book';
        actionInput.value = 'create';
        idInput.value = '';
      }

      if (modalId === 'memberEditorModal') {
        const form = document.querySelector('[data-member-form]');
        const title = document.querySelector('[data-member-modal-title]');
        const actionInput = document.querySelector('[data-member-form-action]');
        const idInput = document.querySelector('[data-member-id]');
        if (!form || !title || !actionInput || !idInput) return;
        form.reset();
        title.textContent = 'Register New Member';
        actionInput.value = 'create';
        idInput.value = '';
      }
    });
  });

  document.querySelectorAll('[data-modal-close]').forEach((button) => {
    button.addEventListener('click', () => closeModal(button.getAttribute('data-modal-close')));
  });

  document.querySelectorAll('.modal-overlay').forEach((modal) => {
    modal.addEventListener('click', (event) => {
      if (event.target === modal) closeModal(modal.id);
    });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      document.querySelectorAll('.modal-overlay:not(.modal-hidden)').forEach((modal) => closeModal(modal.id));
      closeAllDropdowns();
    }
  });

  document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const input = button.parentElement?.querySelector('[data-password-input]');
      if (!input) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      button.classList.toggle('showing', show);
    });
  });

  const forgotForm = document.querySelector('[data-forgot-form]');
  if (forgotForm) {
    forgotForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const emailInput = forgotForm.querySelector('[data-forgot-email]');
      const error = forgotForm.querySelector('[data-forgot-error]');
      const success = document.querySelector('[data-forgot-success]');
      const endpoint = forgotForm.getAttribute('data-forgot-endpoint');
      if (!emailInput || !error || !success || !endpoint) return;

      const email = emailInput.value.trim();
      error.classList.add('modal-hidden');
      error.textContent = '';

      try {
        const response = await fetch(endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            csrf_token: csrfToken,
            email,
          }),
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
          throw new Error(data.message || 'Unable to process reset request.');
        }

        forgotForm.classList.add('modal-hidden');
        success.classList.remove('modal-hidden');
      } catch (errorObject) {
        error.textContent = errorObject.message;
        error.classList.remove('modal-hidden');
      }
    });
  }

  document.querySelectorAll('[data-view-btn]').forEach((button) => {
    button.addEventListener('click', () => {
      const view = button.getAttribute('data-view-btn');
      document.querySelectorAll('[data-view-btn]').forEach((btn) => btn.classList.toggle('active', btn === button));
      document.querySelectorAll('[data-view-panel]').forEach((panel) => {
        panel.classList.toggle('modal-hidden', panel.getAttribute('data-view-panel') !== view);
      });
    });
  });

  const filterBooks = () => {
    const searchInput = document.querySelector('[data-books-page] [data-filter-input]');
    if (!searchInput) return;
    const search = searchInput.value.trim().toLowerCase();
    const activeGenre = document.querySelector('[data-genre-tab].active')?.getAttribute('data-genre-tab') || 'All';
    const visibleKeys = new Set();
    document.querySelectorAll('[data-books-page] [data-filter-item]').forEach((item) => {
      const haystack = `${item.dataset.title || ''} ${item.dataset.author || ''} ${item.dataset.isbn || ''}`;
      const genreMatch = activeGenre === 'All' || item.dataset.genre === activeGenre;
      const searchMatch = haystack.includes(search);
      const show = genreMatch && searchMatch;
      item.classList.toggle('modal-hidden', !show);
      if (show) visibleKeys.add(`${item.dataset.title}-${item.dataset.author}-${item.dataset.isbn}`);
    });
    const visible = visibleKeys.size;
    const count = document.querySelector('[data-books-count]');
    if (count) count.textContent = String(visible);
    const emptyRow = document.querySelector('[data-filter-empty-row]');
    const emptyCard = document.querySelector('[data-books-page] [data-filter-empty]');
    if (emptyRow) emptyRow.classList.toggle('modal-hidden', visible !== 0);
    if (emptyCard) emptyCard.classList.toggle('modal-hidden', visible !== 0);
  };

  const debounce = (callback, delay = 120) => {
    let timer;
    return (...args) => {
      clearTimeout(timer);
      timer = setTimeout(() => callback(...args), delay);
    };
  };

  document.querySelectorAll('[data-genre-tab]').forEach((button) => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-genre-tab]').forEach((tab) => tab.classList.toggle('active', tab === button));
      filterBooks();
    });
  });

  const booksSearch = document.querySelector('[data-books-page] [data-filter-input]');
  if (booksSearch) {
    booksSearch.addEventListener('input', debounce(filterBooks));
    filterBooks();
  }

  const fillBookForm = (book) => {
    const form = document.querySelector('[data-book-form]');
    if (!form) return;
    form.querySelector('[name="title"]').value = book.title || '';
    form.querySelector('[name="author"]').value = book.author || '';
    form.querySelector('[name="isbn"]').value = book.isbn || '';
    form.querySelector('[name="genre"]').value = book.genre || 'Software Eng.';
    form.querySelector('[name="publication_year"]').value = book.publication_year || '';
    form.querySelector('[name="copies_total"]').value = book.copies_total || 1;
    form.querySelector('[name="copies_available"]').value = book.copies_available || 0;
    form.querySelector('[name="description"]').value = book.description || '';
    form.querySelector('[data-book-form-action]').value = 'update';
    form.querySelector('[data-book-id]').value = book.id || '';
    const title = document.querySelector('[data-book-modal-title]');
    if (title) title.textContent = 'Edit Book';
  };

  document.querySelectorAll('[data-edit-book]').forEach((button) => {
    button.addEventListener('click', () => {
      const source = button.closest('[data-book]') || button.closest('tr[data-book]');
      if (!source?.dataset.book) return;
      try {
        fillBookForm(JSON.parse(source.dataset.book));
        openModal('bookEditorModal');
      } catch (error) {
        createToast('Unable to load the selected book.', 'error');
      }
    });
  });

  document.querySelectorAll('[data-delete-book]').forEach((button) => {
    button.addEventListener('click', () => {
      pendingBookDeleteId = button.getAttribute('data-book-id');
      const copy = document.querySelector('[data-delete-copy]');
      if (copy) copy.textContent = `"${button.getAttribute('data-book-title') || 'this book'}" will be permanently removed from the catalogue.`;
      openModal('bookDeleteModal');
    });
  });

  const confirmBookDelete = document.querySelector('[data-confirm-book-delete]');
  if (confirmBookDelete) {
    confirmBookDelete.addEventListener('click', async () => {
      if (!pendingBookDeleteId) return;
      const endpoint = confirmBookDelete.getAttribute('data-endpoint');
      if (!endpoint) return;
      try {
        const response = await fetch(endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            csrf_token: csrfToken,
            action: 'delete',
            book_id: pendingBookDeleteId,
          }),
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
          throw new Error(data.message || 'Book delete failed.');
        }

        document.querySelectorAll(`[data-book-row-id="${pendingBookDeleteId}"], [data-book-card-id="${pendingBookDeleteId}"]`).forEach((node) => node.remove());
        filterBooks();
        createToast(data.message);
        closeModal('bookDeleteModal');
      } catch (errorObject) {
        createToast(errorObject.message, 'error');
      }
    });
  }

  const filterUsers = () => {
    const searchInput = document.querySelector('.users-page [data-filter-input]');
    if (!searchInput) return;
    const search = searchInput.value.trim().toLowerCase();
    const activeRole = document.querySelector('[data-user-role].active')?.getAttribute('data-user-role') || 'all';
    let visible = 0;
    document.querySelectorAll('.users-page [data-filter-item]').forEach((item) => {
      const haystack = `${item.dataset.name || ''} ${item.dataset.email || ''}`;
      const roleMatch = activeRole === 'all' || item.dataset.role === activeRole;
      const show = roleMatch && haystack.includes(search);
      item.classList.toggle('modal-hidden', !show);
      if (show) visible += 1;
    });
    const empty = document.querySelector('.users-page [data-filter-empty]');
    if (empty) empty.classList.toggle('modal-hidden', visible !== 0);
  };

  document.querySelectorAll('[data-user-role]').forEach((button) => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-user-role]').forEach((tab) => tab.classList.toggle('active', tab === button));
      filterUsers();
    });
  });

  const usersSearch = document.querySelector('.users-page [data-filter-input]');
  if (usersSearch) {
    usersSearch.addEventListener('input', debounce(filterUsers));
    filterUsers();
  }

  const memberForm = document.querySelector('[data-member-form]');
  const memberModalTitle = document.querySelector('[data-member-modal-title]');
  document.querySelectorAll('[data-edit-member]').forEach((button) => {
    button.addEventListener('click', () => {
      const source = button.closest('[data-user]');
      if (!source?.dataset.user || !memberForm) return;
      try {
        const user = JSON.parse(source.dataset.user);
        memberForm.reset();
        memberForm.querySelector('[name="name"]').value = user.name || '';
        memberForm.querySelector('[name="email"]').value = user.email || '';
        memberForm.querySelector('[name="role"]').value = user.role || 'student';
        memberForm.querySelector('[name="phone"]').value = user.phone || '';
        memberForm.querySelector('[name="location"]').value = user.location || '';
        memberForm.querySelector('[name="bio"]').value = user.bio || '';
        memberForm.querySelector('[name="student_id"]').value = user.student_id || '';
        memberForm.querySelector('[name="faculty"]').value = user.faculty || '';
        memberForm.querySelector('[name="department"]').value = user.department || '';
        memberForm.querySelector('[data-member-form-action]').value = 'update';
        memberForm.querySelector('[data-member-id]').value = user.id || '';
        if (memberModalTitle) memberModalTitle.textContent = 'Edit Member';
        openModal('memberEditorModal');
      } catch (error) {
        createToast('Unable to load the selected member.', 'error');
      }
    });
  });

  const catalogueSearch = document.querySelector('.catalogue-card [data-filter-input]');
  if (catalogueSearch) {
    const filterCatalogue = () => {
      const query = catalogueSearch.value.trim().toLowerCase();
      let visible = 0;
      document.querySelectorAll('.catalogue-card [data-filter-item]').forEach((item) => {
        const haystack = `${item.dataset.title || ''} ${item.dataset.author || ''}`;
        const show = haystack.includes(query);
        item.classList.toggle('modal-hidden', !show);
        if (show) visible += 1;
      });
      const empty = document.querySelector('.catalogue-card [data-filter-empty]');
      if (empty) empty.classList.toggle('modal-hidden', visible !== 0);
    };
    catalogueSearch.addEventListener('input', debounce(filterCatalogue));
    filterCatalogue();
  }

  const applyLoanFilter = (value) => {
    document.querySelectorAll('[data-loan-filter]').forEach((tab) => tab.classList.toggle('active', tab.getAttribute('data-loan-filter') === value));
    let visible = 0;
    document.querySelectorAll('[data-loan-row]').forEach((row) => {
      const show = value === 'all' || row.getAttribute('data-status') === value;
      row.classList.toggle('modal-hidden', !show);
      if (show) visible += 1;
    });
    const empty = document.querySelector('[data-loan-empty]');
    if (empty) empty.classList.toggle('modal-hidden', visible !== 0);
  };

  const refreshCurrentLoanFilter = () => {
    const active = document.querySelector('[data-loan-filter].active')?.getAttribute('data-loan-filter');
    if (active) applyLoanFilter(active);
  };

  document.querySelectorAll('[data-loan-filter]').forEach((button) => {
    button.addEventListener('click', () => applyLoanFilter(button.getAttribute('data-loan-filter') || 'all'));
  });

  document.querySelectorAll('[data-loan-filter-trigger]').forEach((button) => {
    button.addEventListener('click', () => applyLoanFilter(button.getAttribute('data-loan-filter-trigger') || 'all'));
  });

  if (document.querySelector('[data-loan-row]')) {
    applyLoanFilter('all');
  }

  document.querySelectorAll('[data-loan-return], [data-loan-renew]').forEach((button) => {
    button.addEventListener('click', async () => {
      const endpoint = button.getAttribute('data-loan-action-endpoint');
      const loanId = button.getAttribute('data-loan-id');
      const action = button.hasAttribute('data-loan-return') ? 'return' : 'renew';
      if (!endpoint || !loanId) return;

      try {
        const response = await fetch(endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            csrf_token: csrfToken,
            action,
            loan_id: loanId,
          }),
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
          throw new Error(data.message || 'Loan action failed.');
        }

        const row = document.querySelector(`[data-loan-id="${loanId}"]`);
        if (row && action === 'return') {
          const oldStatus = row.getAttribute('data-status') || 'active';
          row.setAttribute('data-status', 'returned');
          adjustLoanCount(oldStatus, -1);
          adjustLoanCount('returned', 1);
          const statusNode = row.querySelector('[data-loan-status]');
          const dueNode = row.querySelector('[data-loan-days]');
          if (statusNode) {
            statusNode.textContent = 'Returned';
            statusNode.className = 'status-pill status-returned';
          }
          if (dueNode) {
            dueNode.innerHTML = '<span class="days-neutral">-</span>';
          }
          const actions = row.querySelector('.action-btns');
          if (actions) actions.innerHTML = '<span class="returned-label">Completed</span>';
          refreshCurrentLoanFilter();
        }

        if (row && action === 'renew') {
          const oldStatus = row.getAttribute('data-status') || 'active';
          const nextStatus = data.status || 'active';
          const dueCell = row.querySelector('[data-loan-due]');
          if (dueCell) dueCell.textContent = data.due_at;
          row.setAttribute('data-status', nextStatus);
          if (oldStatus !== nextStatus) {
            adjustLoanCount(oldStatus, -1);
            adjustLoanCount(nextStatus, 1);
          }
          const statusNode = row.querySelector('[data-loan-status], .loan-pill');
          if (statusNode) {
            const status = nextStatus;
            statusNode.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            statusNode.className = statusNode.classList.contains('loan-pill')
              ? `loan-pill lp-${status}`
              : `status-pill status-${status}`;
          }
          const daysNode = row.querySelector('[data-loan-days], .loan-row-due');
          if (daysNode) {
            if (daysNode.hasAttribute('data-loan-days')) {
              daysNode.innerHTML = formatLoanDays(data.due_at, data.status || 'active');
            } else {
              daysNode.textContent = `Due: ${data.due_at}`;
            }
          }
          if (Number(data.renewal_count || 0) >= 2) {
            button.remove();
          }
          refreshCurrentLoanFilter();
        }

        createToast(data.message);
      } catch (errorObject) {
        createToast(errorObject.message, 'error');
      }
    });
  });

  document.querySelectorAll('[data-book-request]').forEach((button) => {
    button.addEventListener('click', async () => {
      const endpoint = button.getAttribute('data-request-endpoint');
      const bookId = button.getAttribute('data-book-id');
      if (!endpoint || !bookId || button.disabled) return;

      button.disabled = true;
      const oldText = button.textContent;
      button.textContent = 'Sending...';

      try {
        const response = await fetch(endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            csrf_token: csrfToken,
            action: 'create',
            book_id: bookId,
          }),
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
          throw new Error(data.message || 'Request failed.');
        }

        button.textContent = 'Requested';
        button.classList.add('borrow-disabled');
        if (Array.isArray(data.requests)) {
          renderStudentRequests(data.requests);
        } else {
          addStudentRequestItem(data);
        }
        createToast(data.message);
      } catch (errorObject) {
        button.disabled = false;
        button.textContent = oldText;
        createToast(errorObject.message, 'error');
      }
    });
  });

  document.querySelectorAll('[data-request-action]').forEach((button) => {
    button.addEventListener('click', async () => {
      const endpoint = button.getAttribute('data-request-endpoint');
      const requestId = button.getAttribute('data-request-id');
      const action = button.getAttribute('data-request-action');
      if (!endpoint || !requestId || !action) return;

      const row = button.closest('[data-request-row]');
      row?.classList.add('request-row-busy');
      row?.querySelectorAll('button').forEach((actionButton) => {
        actionButton.disabled = true;
      });

      try {
        const response = await fetch(endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            csrf_token: csrfToken,
            action,
            request_id: requestId,
          }),
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
          throw new Error(data.message || 'Request update failed.');
        }

        row?.classList.add('request-row-done');
        setTimeout(() => {
          row?.remove();
          setAdminRequestsEmptyState();
        }, 220);
        createToast(data.message);
      } catch (errorObject) {
        row?.classList.remove('request-row-busy');
        row?.querySelectorAll('button').forEach((actionButton) => {
          actionButton.disabled = false;
        });
        createToast(errorObject.message, 'error');
      }
    });
  });

  const profileForm = document.querySelector('[data-profile-form]');
  if (profileForm) {
    profileForm.addEventListener('input', (event) => {
      const target = event.target;
      const field = target.getAttribute('data-profile-field');
      if (!field) return;
      document.querySelectorAll(`[data-profile-preview="${field}"]`).forEach((node) => {
        node.textContent = target.value;
      });
    });
  }

  const apiBookSearchButton = document.querySelector('[data-api-book-search]');
  if (apiBookSearchButton) {
    apiBookSearchButton.addEventListener('click', async () => {
      const queryInput = document.querySelector('[data-api-book-query]');
      const statusNode = document.querySelector('[data-api-book-status]');
      const resultsNode = document.querySelector('[data-api-book-results]');
      if (!queryInput || !statusNode || !resultsNode) return;

      const query = queryInput.value.trim();
      if (!query) {
        statusNode.textContent = 'Enter an ISBN or title first.';
        statusNode.classList.remove('modal-hidden');
        resultsNode.classList.add('modal-hidden');
        resultsNode.innerHTML = '';
        return;
      }

      statusNode.textContent = 'Searching...';
      statusNode.classList.remove('modal-hidden');
      resultsNode.classList.add('modal-hidden');
      resultsNode.innerHTML = '';

      const endpoint = /^\d+$/.test(query.replace(/[-\s]/g, ''))
        ? `https://openlibrary.org/search.json?isbn=${encodeURIComponent(query)}`
        : `https://openlibrary.org/search.json?title=${encodeURIComponent(query)}`;

      try {
        const response = await fetch(endpoint);
        const data = await response.json();
        const docs = (data.docs || []).slice(0, 6);
        if (!docs.length) {
          statusNode.textContent = 'No results found.';
          return;
        }

        statusNode.classList.add('modal-hidden');
        resultsNode.classList.remove('modal-hidden');
        resultsNode.innerHTML = docs.map((doc) => {
          const title = doc.title || 'Unknown title';
          const author = (doc.author_name && doc.author_name[0]) || 'Unknown author';
          const year = doc.first_publish_year || '';
          const isbn = (doc.isbn && doc.isbn[0]) || '';
          const fillPayload = JSON.stringify({
            title,
            author,
            publication_year: year,
            isbn,
            genre: 'Software Eng.',
            copies_total: 1,
            copies_available: 1,
            description: `Added from catalogue search for "${query}".`,
          }).replace(/'/g, '&#039;');
          return `
            <div class="book-card api-book-card">
              <div class="book-card-body">
                <div class="book-card-header">
                  <span class="genre-tag">Lookup</span>
                </div>
                <h4 class="book-card-title">${escapeHtml(title)}</h4>
                <p class="book-card-author">${escapeHtml(author)}</p>
                <div class="book-card-meta">
                  <span>${escapeHtml(year)}</span>
                  <span>${escapeHtml(isbn)}</span>
                </div>
                <div class="book-card-actions">
                  <button type="button" class="act-btn act-edit" data-api-fill-book='${fillPayload}'>Use This</button>
                </div>
              </div>
            </div>
          `;
        }).join('');

        resultsNode.querySelectorAll('[data-api-fill-book]').forEach((button) => {
          button.addEventListener('click', () => {
            try {
              const book = JSON.parse(button.getAttribute('data-api-fill-book'));
              fillBookForm(book);
              document.querySelector('[data-book-form-action]').value = 'create';
              document.querySelector('[data-book-id]').value = '';
              const title = document.querySelector('[data-book-modal-title]');
              if (title) title.textContent = 'Add New Book';
              openModal('bookEditorModal');
            } catch (error) {
              createToast('Could not add this result to the form.', 'error');
            }
          });
        });
      } catch (errorObject) {
        statusNode.textContent = 'Search failed. Check your internet connection.';
      }
    });
  }
});
