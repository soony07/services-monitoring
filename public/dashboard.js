const root = document.documentElement;
const savedTheme = localStorage.getItem('pulse-theme');
if (savedTheme) root.dataset.theme = savedTheme;

document.querySelector('#theme').addEventListener('click', () => {
    const dark = root.dataset.theme === 'dark'
        || (!root.dataset.theme && matchMedia('(prefers-color-scheme: dark)').matches);
    root.dataset.theme = dark ? 'light' : 'dark';
    localStorage.setItem('pulse-theme', root.dataset.theme);
});

const search = document.querySelector('#search');
search?.addEventListener('input', () => {
    const query = search.value.trim().toLowerCase();
    document.querySelectorAll('[data-monitor]').forEach(item => {
        item.hidden = !item.textContent.toLowerCase().includes(query);
    });
});

const kind = document.querySelector('#kind');
if (kind) {
    const keyword = document.querySelector('#keyword');
    const update = () => {
        keyword.hidden = kind.value !== 'keyword';
        keyword.querySelector('input').required = !keyword.hidden;
    };
    update();
    kind.addEventListener('change', update);
}

if (document.body.hasAttribute('data-refresh')) {
    setInterval(async () => {
        if (document.hidden) return;
        const selector = document.querySelector('.status-page') ? '.status-page' : '.content';
        try {
            const response = await fetch(location.href, { cache: 'no-store' });
            if (!response.ok || document.hidden) return;
            const html = new DOMParser().parseFromString(await response.text(), 'text/html');
            const current = document.querySelector(selector);
            const updated = html.querySelector(selector);
            if (updated && !current.contains(document.activeElement)) {
                current.replaceWith(updated);
                const oldRows = document.querySelectorAll('[data-monitor]');
                const newRows = html.querySelectorAll('[data-monitor]');
                oldRows.forEach((row, index) => {
                    if (row.getAttribute('href') === newRows[index]?.getAttribute('href')) {
                        row.querySelector('.dot').className = newRows[index].querySelector('.dot').className;
                    }
                });
            }
        } catch {
            // Keep the last displayed results during a temporary connection failure.
        }
    }, 15000);
}


const settingsMenu = document.querySelector('.settings-menu');
if (settingsMenu) {
    document.addEventListener('click', event => {
        if (!settingsMenu.contains(event.target)) settingsMenu.open = false;
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && settingsMenu.open) {
            settingsMenu.open = false;
            settingsMenu.querySelector('summary').focus();
        }
    });
}
