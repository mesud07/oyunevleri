(() => {
  const tabs = [...document.querySelectorAll('[data-profile-section-tab]')];
  const panels = [...document.querySelectorAll('[data-profile-section-panel]')];
  const content = document.querySelector('[data-profile-section-content]');
  const navigation = document.querySelector('.student-tabs');

  if (!tabs.length || !panels.length || !content || !navigation) {
    return;
  }

  panels.forEach((panel) => content.appendChild(panel));

  function closePanels() {
    tabs.forEach((tab) => {
      tab.classList.remove('is-active');
      tab.setAttribute('aria-selected', 'false');
    });
    panels.forEach((panel) => { panel.hidden = true; });
    content.hidden = true;
  }

  function openPanel(name, updateHash = true) {
    const selectedTab = tabs.find((tab) => tab.dataset.profileSectionTab === name);
    const selectedPanel = panels.find((panel) => panel.dataset.profileSectionPanel === name);
    if (!selectedTab || !selectedPanel) {
      return;
    }

    navigation.querySelectorAll('.is-active').forEach((item) => item.classList.remove('is-active'));
    tabs.forEach((tab) => {
      const active = tab === selectedTab;
      tab.classList.toggle('is-active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    panels.forEach((panel) => { panel.hidden = panel !== selectedPanel; });
    content.hidden = false;

    if (updateHash && window.history?.replaceState) {
      window.history.replaceState(null, '', `#${name}`);
    }
  }

  navigation.addEventListener('click', (event) => {
    const tab = event.target.closest('[data-profile-section-tab]');
    if (tab) {
      openPanel(tab.dataset.profileSectionTab || '');
      return;
    }

    if (event.target.closest('[data-profile-anchor]')) {
      closePanels();
      navigation.querySelectorAll('.is-active').forEach((item) => item.classList.remove('is-active'));
      event.target.closest('[data-profile-anchor]')?.classList.add('is-active');
    }
  });

  const initial = window.location.hash.slice(1);
  if (tabs.some((tab) => tab.dataset.profileSectionTab === initial)) {
    openPanel(initial, false);
  }
})();
