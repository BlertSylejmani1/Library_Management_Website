document.addEventListener('DOMContentLoaded', () => {
  const body = document.body;
  const root = document.documentElement;
  const cookieDays = 30;

  const setCookie = (name, value, days = cookieDays) => {
    const expires = new Date(Date.now() + days * 86400000).toUTCString();
    document.cookie = `${name}=${value}; expires=${expires}; path=/`;
  };

  const syncDarkClassTargets = (dark) => {
    const selectors = [
      '.app-shell',
      '.app-main',
      '.sidebar',
      '.topnav',
      '.dashboard',
      '.student-dash',
      '.books-page',
      '.loans-page',
      '.users-page',
      '.profile-page'
    ];

    selectors.forEach((selector) => {
      document.querySelectorAll(selector).forEach((element) => {
        element.classList.toggle('dark', dark);
      });
    });
  };

  const createToast = (message, type = 'success') => {
    if (!message) return;
    const toast = document.createElement('div');
    toast.className = `page-alert alert-${type}`;
    toast.innerHTML = `${type === 'error' ? '❌' : type === 'info' ? 'ℹ️' : '✅'} ${message}`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2800);
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

  document.querySelectorAll('[data-modal-open]').forEach((button) => {
    button.addEventListener('click', () => {
      const modalId = button.getAttribute('data-modal-open');
      openModal(modalId);
      if (modalId === 'bookEditorModal') {
        const form = document.querySelector('#bookEditorModal form');
        const title = document.querySelector('[data-book-modal-title]');
        if (!form || !title) return;
        const mode = button.getAttribute('data-book-mode') || 'edit';
        const bookData = button.closest('[data-book]')?.dataset.book || button.closest('tr')?.dataset.book;
        form.reset();
        title.textContent = mode === 'add' ? 'Add New Book' : 'Edit Book';
        if (bookData) {
          try {
            const book = JSON.parse(bookData);
            form.querySelector('[name="title"]').value = book.title || '';
            form.querySelector('[name="author"]').value = book.author || '';
            form.querySelector('[name="isbn"]').value = book.isbn || '';
            form.querySelector('[name="genre"]').value = book.genre || 'Software Eng.';
            form.querySelector('[name="year"]').value = book.year || '';
            form.querySelector('[name="copies"]').value = book.copies || 1;
          } catch (error) {
            console.error(error);
          }
        }
      }
      if (modalId === 'bookDeleteModal') {
        const title = button.getAttribute('data-book-title') || 'this book';
        const copy = document.querySelector('[data-delete-copy]');
        if (copy) {
          copy.textContent = `"${title}" will be permanently removed from the catalogue.`;
        }
      }
      if (modalId === 'forgotPasswordModal') {
        const form = document.querySelector('[data-forgot-form]');
        const success = document.querySelector('[data-forgot-success]');
        const error = document.querySelector('[data-forgot-error]');
        if (form && success && error) {
          form.classList.remove('modal-hidden');
          success.classList.add('modal-hidden');
          error.classList.add('modal-hidden');
          error.textContent = '';
          form.reset();
        }
      }
    });
  });

  document.querySelectorAll('[data-modal-close]').forEach((button) => {
    button.addEventListener('click', () => closeModal(button.getAttribute('data-modal-close')));
  });

  document.querySelectorAll('.modal-overlay').forEach((modal) => {
    modal.addEventListener('click', (event) => {
      if (event.target === modal) {
        closeModal(modal.id);
      }
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
    forgotForm.addEventListener('submit', (event) => {
      event.preventDefault();
      const emailInput = forgotForm.querySelector('[data-forgot-email]');
      const error = forgotForm.querySelector('[data-forgot-error]');
      const success = document.querySelector('[data-forgot-success]');
      if (!emailInput || !error || !success) return;
      const email = emailInput.value.trim();
      error.classList.add('modal-hidden');
      error.textContent = '';
      if (!email) {
        error.textContent = 'Please enter your email address.';
        error.classList.remove('modal-hidden');
        return;
      }
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        error.textContent = 'Please enter a valid email address.';
        error.classList.remove('modal-hidden');
        return;
      }
      forgotForm.classList.add('modal-hidden');
      success.classList.remove('modal-hidden');
    });
  }

  document.querySelectorAll('[data-static-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      createToast(form.getAttribute('data-success-message') || 'Saved successfully.');
      const closeId = form.getAttribute('data-close-modal');
      if (closeId) closeModal(closeId);
    });
  });

  document.querySelectorAll('[data-toast-message]').forEach((button) => {
    button.addEventListener('click', () => {
      createToast(button.getAttribute('data-toast-message'), button.getAttribute('data-toast-type') || 'success');
      const closeId = button.getAttribute('data-modal-close');
      if (closeId) closeModal(closeId);
    });
  });

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

  document.querySelectorAll('[data-genre-tab]').forEach((button) => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-genre-tab]').forEach((tab) => tab.classList.toggle('active', tab === button));
      filterBooks();
    });
  });

  const booksSearch = document.querySelector('[data-books-page] [data-filter-input]');
  if (booksSearch) {
    booksSearch.addEventListener('input', filterBooks);
    filterBooks();
  }

  document.querySelectorAll('[data-edit-book]').forEach((button) => {
    button.addEventListener('click', () => {
      const form = document.querySelector('#bookEditorModal form');
      const title = document.querySelector('[data-book-modal-title]');
      const source = button.closest('[data-book]') || button.closest('tr[data-book]');
      if (form && title) {
        form.reset();
        title.textContent = 'Edit Book';
        if (source?.dataset.book) {
          try {
            const book = JSON.parse(source.dataset.book);
            form.querySelector('[name="title"]').value = book.title || '';
            form.querySelector('[name="author"]').value = book.author || '';
            form.querySelector('[name="isbn"]').value = book.isbn || '';
            form.querySelector('[name="genre"]').value = book.genre || 'Software Eng.';
            form.querySelector('[name="year"]').value = book.year || '';
            form.querySelector('[name="copies"]').value = book.copies || 1;
          } catch (error) {
            console.error(error);
          }
        }
      }
      openModal('bookEditorModal');
    });
  });

  document.querySelectorAll('[data-delete-book]').forEach((button) => {
    button.addEventListener('click', () => {
      const copy = document.querySelector('[data-delete-copy]');
      if (copy) copy.textContent = `"${button.getAttribute('data-book-title') || 'this book'}" will be permanently removed from the catalogue.`;
      openModal('bookDeleteModal');
    });
  });

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
    usersSearch.addEventListener('input', filterUsers);
    filterUsers();
  }

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
    catalogueSearch.addEventListener('input', filterCatalogue);
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

  document.querySelectorAll('[data-loan-filter]').forEach((button) => {
    button.addEventListener('click', () => applyLoanFilter(button.getAttribute('data-loan-filter') || 'all'));
  });
  document.querySelectorAll('[data-loan-filter-trigger]').forEach((button) => {
    button.addEventListener('click', () => applyLoanFilter(button.getAttribute('data-loan-filter-trigger') || 'all'));
  });

  if (document.querySelector('[data-loan-row]')) {
    applyLoanFilter('all');
  }

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

    profileForm.addEventListener('reset', () => {
      setTimeout(() => {
        profileForm.querySelectorAll('[data-profile-field]').forEach((field) => {
          const key = field.getAttribute('data-profile-field');
          document.querySelectorAll(`[data-profile-preview="${key}"]`).forEach((node) => {
            node.textContent = field.value;
          });
        });
      }, 0);
    });
  }
});
