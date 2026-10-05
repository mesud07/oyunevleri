(function () {
  const root = document.documentElement;

  function storageGet(key) {
    try {
      return window.localStorage.getItem(key);
    } catch (error) {
      return null;
    }
  }

  function storageSet(key, value) {
    try {
      window.localStorage.setItem(key, value);
    } catch (error) {
      // Depolama kapalı olsa da kontroller mevcut sayfada çalışmaya devam eder.
    }
  }

  const themeToggle = document.querySelector('[data-theme-toggle]');
  const themeLabel = themeToggle?.querySelector('[data-theme-toggle-label]');

  function applyTheme(theme, save) {
    const dark = theme === 'dark';
    root.dataset.theme = dark ? 'dark' : 'light';
    root.style.colorScheme = dark ? 'dark' : 'light';
    themeToggle?.setAttribute('aria-pressed', dark ? 'true' : 'false');
    themeToggle?.setAttribute('aria-label', dark ? 'Gündüz moduna geç' : 'Gece moduna geç');
    if (themeLabel) themeLabel.textContent = dark ? 'Gündüz Modu' : 'Gece Modu';
    if (save) storageSet('talyaTema', dark ? 'dark' : 'light');
  }

  themeToggle?.addEventListener('click', function (event) {
    event.preventDefault();
    applyTheme(root.dataset.theme === 'dark' ? 'light' : 'dark', true);
  });
  applyTheme(root.dataset.theme === 'dark' ? 'dark' : 'light', false);

  document.querySelectorAll('[data-print-page]').forEach((button) => {
    button.addEventListener('click', () => window.print());
  });

  const sidebarToggles = Array.from(document.querySelectorAll('[data-sidebar-toggle]'));
  const mobileQuery = window.matchMedia('(max-width: 900px)');

  function setSidebarToggleState(expanded) {
    sidebarToggles.forEach((button) => button.setAttribute('aria-expanded', expanded ? 'true' : 'false'));
  }

  function closeSidebarFlyouts(except = null) {
    document.querySelectorAll('.menu-group.is-flyout-open').forEach((group) => {
      if (group === except) return;
      group.classList.remove('is-flyout-open');
      const toggle = group.querySelector('[data-menu-group-toggle]');
      toggle?.setAttribute('aria-expanded', 'false');
    });
  }

  function applySidebarState() {
    closeSidebarFlyouts();
    if (mobileQuery.matches) {
      root.classList.add('sidebar-collapsed');
      root.classList.remove('sidebar-expanded');
      setSidebarToggleState(root.classList.contains('sidebar-mobile-open'));
      return;
    }
    const collapsed = storageGet('talyaSidebarCollapsed') === '1';
    root.classList.remove('sidebar-mobile-open');
    document.body.classList.remove('has-sidebar-open');
    root.classList.toggle('sidebar-collapsed', collapsed);
    root.classList.toggle('sidebar-expanded', !collapsed);
    document.querySelectorAll('.menu-group').forEach((group) => {
      const toggle = group.querySelector('[data-menu-group-toggle]');
      toggle?.setAttribute('aria-expanded', !collapsed && group.classList.contains('is-open') ? 'true' : 'false');
    });
    setSidebarToggleState(!collapsed);
  }

  function closeMobileSidebar() {
    root.classList.remove('sidebar-mobile-open');
    document.body.classList.remove('has-sidebar-open');
    applySidebarState();
  }

  document.querySelectorAll('[data-sidebar-close]').forEach((button) => button.addEventListener('click', closeMobileSidebar));
  mobileQuery.addEventListener?.('change', applySidebarState);

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeSidebarFlyouts();
      closeMobileSidebar();
    }
  });

  document.addEventListener('click', function (event) {
    const target = event.target instanceof Element ? event.target : event.target?.parentElement;
    if (!target) return;

    const menuToggle = target.closest('[data-menu-group-toggle]');
    if (menuToggle) {
      event.preventDefault();
      const group = menuToggle.closest('.menu-group');
      if (!group) return;
      const collapsedDesktop = !mobileQuery.matches && root.classList.contains('sidebar-collapsed');
      if (collapsedDesktop) {
        const willOpen = !group.classList.contains('is-flyout-open');
        closeSidebarFlyouts(group);
        group.classList.toggle('is-flyout-open', willOpen);
        menuToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        const submenu = group.querySelector('.submenu');
        submenu?.style.removeProperty('--flyout-shift');
        if (willOpen && submenu) {
          const rect = submenu.getBoundingClientRect();
          const overflow = rect.bottom - (window.innerHeight - 16);
          if (overflow > 0) {
            submenu.style.setProperty('--flyout-shift', `${-overflow}px`);
          }
        }
        return;
      }
      const isOpen = group.classList.toggle('is-open');
      menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      return;
    }

    const sidebarToggle = target.closest('[data-sidebar-toggle]');
    if (sidebarToggle) {
      event.preventDefault();
      if (mobileQuery.matches) {
        const open = !root.classList.contains('sidebar-mobile-open');
        root.classList.toggle('sidebar-mobile-open', open);
        document.body.classList.toggle('has-sidebar-open', open);
        setSidebarToggleState(open);
      } else {
        const collapsed = !root.classList.contains('sidebar-collapsed');
        storageSet('talyaSidebarCollapsed', collapsed ? '1' : '0');
        applySidebarState();
      }
      return;
    }

    if (!target.closest('.menu-group.is-flyout-open')) {
      closeSidebarFlyouts();
    }

    if (mobileQuery.matches && root.classList.contains('sidebar-mobile-open') && !target.closest('.sidebar')) {
      closeMobileSidebar();
    }
  });

  applySidebarState();
})();
