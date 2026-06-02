/**
 * theme.js — Dark / Light mode manager
 * Include this as the FIRST script in <head> to prevent flash.
 */

(function () {
    const saved = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
})();

function getTheme() {
    return document.documentElement.getAttribute('data-theme') || 'light';
}

function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    _updateAllToggleBtns(theme);
}

function toggleTheme() {
    setTheme(getTheme() === 'dark' ? 'light' : 'dark');
}

function _updateAllToggleBtns(theme) {
    document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
        btn.textContent = theme === 'dark' ? '亮色' : '暗色';
        btn.setAttribute('title', theme === 'dark' ? '切換亮色模式' : '切換暗色模式');
    });
}

function mountFloatingThemeToggle() {
    const btn = document.createElement('button');
    btn.className = 'theme-toggle-btn theme-toggle-floating';
    btn.textContent = getTheme() === 'dark' ? '亮色' : '暗色';
    btn.title = getTheme() === 'dark' ? '切換亮色模式' : '切換暗色模式';
    btn.addEventListener('click', toggleTheme);
    document.body.appendChild(btn);
}

function createNavThemeToggle() {
    const btn = document.createElement('button');
    btn.className = 'btn btn-sm theme-toggle-btn';
    btn.textContent = getTheme() === 'dark' ? '亮色' : '暗色';
    btn.title = getTheme() === 'dark' ? '切換亮色模式' : '切換暗色模式';
    btn.addEventListener('click', toggleTheme);
    return btn;
}
