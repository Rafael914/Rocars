document.addEventListener('DOMContentLoaded', () => {
      const toggleBtn   = document.getElementById('toggleBtn');
      const sidebar     = document.getElementById('sidebar');
      const mainContent = document.getElementById('mainContent');

      if (!toggleBtn || !sidebar || !mainContent) {
        console.error('Sidebar elements missing!');
        return;
      }

      const toggleSidebar = () => {
        const isHidden = sidebar.classList.toggle('hidden');
        mainContent.classList.toggle('expanded', isHidden);
        toggleBtn.setAttribute('aria-expanded', !isHidden);
      };

      toggleBtn.addEventListener('click', toggleSidebar);

      // Initial state
      if (sidebar.classList.contains('hidden')) {
        mainContent.classList.add('expanded');
        toggleBtn.setAttribute('aria-expanded', 'false');
      }
    });