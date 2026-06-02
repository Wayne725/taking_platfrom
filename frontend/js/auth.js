/**
 * auth.js - Auth state helpers used across all pages
 */

/**
 * Returns the currently cached user from sessionStorage, or null.
 */
function getCachedUser() {
    try {
        const raw = sessionStorage.getItem('currentUser');
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

/**
 * Fetches /auth/me from the server and caches result.
 * Returns user object or null if not logged in.
 */
async function fetchCurrentUser() {
    try {
        const res = await Auth.me();
        const user = res.data.user;
        sessionStorage.setItem('currentUser', JSON.stringify(user));
        return user;
    } catch {
        sessionStorage.removeItem('currentUser');
        return null;
    }
}

/**
 * Redirects to login.html if the user is not authenticated.
 * Returns the user object when authenticated.
 */
async function requireLogin() {
    const user = await fetchCurrentUser();
    if (!user) {
        window.location.href = 'login.html';
        return null;
    }
    return user;
}

/**
 * Logs the user out and redirects to login page.
 */
async function logout() {
    try {
        await Auth.logout();
    } catch (_) { /* ignore */ }
    sessionStorage.removeItem('currentUser');
    window.location.href = 'login.html';
}

/**
 * Renders a small nav bar with username + logout button into `containerId`.
 */
function renderNavBar(containerId, user) {
    const container = document.getElementById(containerId);
    if (!container || !user) return;
    container.innerHTML = '';

    const nav = document.createElement('nav');
    nav.className = 'navbar';

    const left = document.createElement('div');
    left.className = 'nav-left';

    const brand = document.createElement('a');
    brand.href = 'index.html';
    brand.className = 'nav-brand';
    brand.textContent = '接案平台';
    left.appendChild(brand);

    const right = document.createElement('div');
    right.className = 'nav-right';

    const userSpan = document.createElement('span');
    userSpan.className = 'nav-user';
    userSpan.textContent = user.username;

    const profileLink = document.createElement('a');
    profileLink.href = 'profile.html';
    profileLink.className = 'btn btn-sm btn-outline';
    profileLink.textContent = '我的資料';

    const dashLink = document.createElement('a');
    dashLink.href = 'dashboard.html';
    dashLink.className = 'btn btn-sm btn-outline';
    dashLink.textContent = '我的儀表板';

    const logoutBtn = document.createElement('button');
    logoutBtn.className = 'btn btn-sm btn-danger';
    logoutBtn.textContent = '登出';
    logoutBtn.addEventListener('click', logout);

    right.appendChild(userSpan);
    right.appendChild(profileLink);
    right.appendChild(dashLink);
    if (typeof createNavThemeToggle === 'function') {
        right.appendChild(createNavThemeToggle());
    }
    right.appendChild(logoutBtn);

    nav.appendChild(left);
    nav.appendChild(right);
    container.appendChild(nav);
}
