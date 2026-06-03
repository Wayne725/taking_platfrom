/**
 * api.js - Centralised Fetch wrapper for all API calls
 * Base URL points to the PHP router.
 */

// 本地開發用相對路徑；線上自動改成你的 InfinityFree 後端網址
const API_BASE = (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')
    ? '../server/api_entry.php'
    : 'https://taking-platfrom.onrender.com/api_entry.php';

/**
 * Low-level request helper.
 * Returns the parsed JSON body, or throws an Error with the server message.
 */
async function request(path, options = {}) {
    // Split off any inline query string from path (e.g. /tasks?status=open)
    const [cleanPath, inlineQS] = path.split('?');
    let url = `${API_BASE}?_path=${encodeURIComponent(cleanPath)}`;
    if (inlineQS) url += '&' + new URLSearchParams(inlineQS).toString();

    const defaults = {
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            'X-Session-ID': sessionStorage.getItem('_sid') || '',
        },
    };

    const config = {
        ...defaults,
        ...options,
        headers: { ...defaults.headers, ...(options.headers || {}) },
    };

    // Body must be JSON-stringified when present
    if (config.body && typeof config.body === 'object') {
        config.body = JSON.stringify(config.body);
    }

    const res = await fetch(url, config);
    const json = await res.json();

    if (json.status !== 'success') {
        const err = new Error(json.message || '發生錯誤');
        err.data = json.data;
        err.httpStatus = res.status;
        throw err;
    }

    return json;
}

// ── Auth ───────────────────────────────────────────────────────────────────────
const Auth = {
    register:      (data) => request('/auth/register',  { method: 'POST', body: data }),
    login:         (data) => request('/auth/login',     { method: 'POST', body: data }),
    logout:        ()     => request('/auth/logout',    { method: 'POST' }),
    me:            ()     => request('/auth/me',         { method: 'GET'  }),
    profile:       ()     => request('/auth/profile',   { method: 'GET'  }),
    updateProfile: (data) => request('/auth/profile',   { method: 'PUT',  body: data }),
};

// ── Tasks ──────────────────────────────────────────────────────────────────────
const Tasks = {
    list:     (params = {}) => {
        const qs = new URLSearchParams(params).toString();
        return request('/tasks' + (qs ? '?' + qs : ''), { method: 'GET' });
    },
    get:      (id)          => request(`/tasks/${id}`,          { method: 'GET'    }),
    create:   (data)        => request('/tasks',                 { method: 'POST',   body: data }),
    update:   (id, data)    => request(`/tasks/${id}`,           { method: 'PUT',    body: data }),
    delete:   (id)          => request(`/tasks/${id}`,           { method: 'DELETE' }),
    apply:    (id, data={}) => request(`/tasks/${id}/apply`,     { method: 'POST',   body: data }),
    assign:   (id, data)    => request(`/tasks/${id}/assign`,    { method: 'POST',   body: data }),
    start:    (id)          => request(`/tasks/${id}/start`,     { method: 'POST' }),
    complete: (id)          => request(`/tasks/${id}/complete`,  { method: 'POST' }),
    confirm:  (id)          => request(`/tasks/${id}/confirm`,   { method: 'POST' }),
    withdraw: (id)          => request(`/tasks/${id}/withdraw`,  { method: 'POST' }),
    review:   (id, data)    => request(`/tasks/${id}/review`,    { method: 'POST', body: data }),
    chat:     (id, params = {}) => {
        const qs = new URLSearchParams(params).toString();
        return request(`/tasks/${id}/chat` + (qs ? '?' + qs : ''), { method: 'GET' });
    },
    sendChat: (id, data)    => request(`/tasks/${id}/chat`,       { method: 'POST', body: data }),
};

const Users = {
    get: (id) => request(`/users/${id}`, { method: 'GET' }),
};
