(() => {
  try {
    if (localStorage.getItem('oyunevleriTheme') === 'dark') {
      document.documentElement.dataset.theme = 'dark';
    }
  } catch (error) {
    // Depolama kapaliysa varsayilan acik tema kullanilir.
  }
})();
