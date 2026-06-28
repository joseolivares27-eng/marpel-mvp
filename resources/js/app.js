import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

if ('serviceWorker' in navigator && window.location.pathname.startsWith('/tecnico')) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js', { scope: '/tecnico/' }).catch(() => {});
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

window.shareOrOpenPdf = async function (url, filename, title) {
    try {
        const response = await fetch(url, { credentials: 'same-origin' });

        if (!response.ok) {
            throw new Error('No se pudo descargar el PDF');
        }

        const blob = await response.blob();
        const file = new File([blob], filename, { type: 'application/pdf' });

        if (navigator.canShare && navigator.canShare({ files: [file] })) {
            await navigator.share({ files: [file], title: title || filename });
            return;
        }

        window.open(URL.createObjectURL(blob), '_blank');
    } catch (error) {
        if (error && error.name === 'AbortError') {
            return;
        }

        window.open(url, '_blank');
    }
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
