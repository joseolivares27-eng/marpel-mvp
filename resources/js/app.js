import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js').catch(() => {});
    });
}

document.querySelectorAll('[data-draft-key]').forEach((field) => {
    const key = `marpel-draft:${field.dataset.draftKey}`;
    const stored = localStorage.getItem(key);

    if (stored && !field.value) {
        field.value = stored;
    }

    field.addEventListener('input', () => {
        localStorage.setItem(key, field.value);
    });

    field.form?.addEventListener('submit', () => {
        localStorage.removeItem(key);
    });
});
