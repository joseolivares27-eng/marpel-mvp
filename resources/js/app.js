import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js').catch(() => {});
    });
}

window.openGoogleMaps = function (appUrl, androidUrl, webUrl) {
    const isAndroid = /Android/i.test(navigator.userAgent);
    const target = isAndroid ? androidUrl : appUrl;

    const fallbackTimer = setTimeout(() => {
        window.location.href = webUrl;
    }, 1200);

    window.addEventListener('pagehide', () => clearTimeout(fallbackTimer), { once: true });
    window.addEventListener('blur', () => clearTimeout(fallbackTimer), { once: true });

    window.location.href = target;
};

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
