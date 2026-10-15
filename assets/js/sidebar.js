(() => {
  const frame = document.querySelector('.app-frame');
  const sidebar = document.getElementById('appSidebar');
  const toggles = [...document.querySelectorAll('[data-sidebar-toggle]')];
  if (!frame || !sidebar || !toggles.length) return;

  const storageKey = 'servimeters_sidebar_collapsed_v1';
  const narrowScreen = window.matchMedia('(max-width: 980px)');
  let storedState = null;
  try {
    storedState = window.localStorage.getItem(storageKey);
  } catch (_) {}

  let collapsed = narrowScreen.matches || storedState === '1';

  const render = () => {
    frame.classList.toggle('sidebar-collapsed', collapsed);
    sidebar.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    toggles.forEach((toggle) => {
      toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
      if (toggle.classList.contains('sidebar-toggle')) {
        toggle.title = collapsed ? 'Mostrar panel' : 'Ocultar panel';
      }
    });
  };

  const persist = () => {
    if (narrowScreen.matches) return;
    try {
      storedState = collapsed ? '1' : '0';
      window.localStorage.setItem(storageKey, storedState);
    } catch (_) {}
  };

  toggles.forEach((toggle) => toggle.addEventListener('click', () => {
    collapsed = !collapsed;
    render();
    persist();
  }));

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && narrowScreen.matches && !collapsed) {
      collapsed = true;
      render();
      persist();
    }
  });

  narrowScreen.addEventListener('change', (event) => {
    collapsed = event.matches ? true : storedState === '1';
    render();
  });

  render();
})();
