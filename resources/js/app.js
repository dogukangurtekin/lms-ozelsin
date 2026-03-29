import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
window.deferredPwaPrompt = null;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('main table').forEach((table) => {
        const parent = table.parentElement;
        if (!parent) return;
        const hasWrap = parent.classList.contains('mobile-table-wrap') || parent.classList.contains('overflow-x-auto');
        if (hasWrap) return;

        const wrap = document.createElement('div');
        wrap.className = 'mobile-table-wrap';
        parent.insertBefore(wrap, table);
        wrap.appendChild(table);
    });

    registerServiceWorker();
    initPushControls();
    initPwaInstallControls();
});

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    window.deferredPwaPrompt = event;

    document.querySelectorAll('[data-pwa-install]').forEach((button) => {
        button.disabled = false;
        button.classList.remove('hidden');
    });
});

async function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    const baseUrl = document.body?.dataset?.appBaseUrl || window.location.origin;
    const serviceWorkerUrl = `${baseUrl.replace(/\/$/, '')}/sw.js`;

    try {
        await navigator.serviceWorker.register(serviceWorkerUrl);
    } catch (error) {
        console.error('Service worker kaydedilemedi.', error);
    }
}

function initPushControls() {
    const enableButton = document.querySelector('[data-push-enable]');
    const disableButton = document.querySelector('[data-push-disable]');
    const statusBox = document.querySelector('[data-push-status]');
    const countBox = document.querySelector('[data-push-count]');
    const baseUrl = getAppBaseUrl();

    if (!enableButton || !statusBox) {
        return;
    }

    const setStatus = (message, tone = 'slate') => {
        statusBox.textContent = message;
        statusBox.className = `rounded-lg border px-3 py-2 text-sm ${
            tone === 'green'
                ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                : tone === 'rose'
                    ? 'border-rose-200 bg-rose-50 text-rose-700'
                    : 'border-slate-200 bg-slate-50 text-slate-600'
        }`;
    };

    const setBusy = (busy) => {
        enableButton.disabled = busy;
        if (disableButton) {
            disableButton.disabled = busy;
        }
    };

    enableButton.addEventListener('click', async () => {
        setBusy(true);

        try {
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                throw new Error('Bu tarayici push bildirimi desteklemiyor.');
            }

            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                throw new Error('Bildirim izni verilmedi.');
            }

            const registration = await navigator.serviceWorker.ready;
            const publicKeyResponse = await window.axios.get(`${baseUrl}/webpush/public-key`);
            const publicKey = publicKeyResponse.data.publicKey;
            const existingSubscription = await registration.pushManager.getSubscription();

            if (existingSubscription) {
                await existingSubscription.unsubscribe();
            }

            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(publicKey),
            });

            await window.axios.post(`${baseUrl}/webpush/subscribe`, {
                endpoint: subscription.endpoint,
                keys: subscription.toJSON().keys,
                contentEncoding: 'aes128gcm',
            });

            if (countBox) {
                countBox.dataset.count = '1';
                countBox.textContent = '1 cihaz bagli';
            }

            setStatus('Bu cihaz icin push bildirimi aktif.', 'green');
        } catch (error) {
            const message = error?.response?.data?.message || error?.message || 'Bildirim acilamadi.';
            setStatus(message, 'rose');
        } finally {
            setBusy(false);
        }
    });

    if (!disableButton) {
        return;
    }

    disableButton.addEventListener('click', async () => {
        setBusy(true);

        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();

            if (subscription) {
                await window.axios.delete(`${baseUrl}/webpush/unsubscribe`, {
                    data: { endpoint: subscription.endpoint },
                });
                await subscription.unsubscribe();
            }

            if (countBox) {
                countBox.dataset.count = '0';
                countBox.textContent = 'Bagli cihaz yok';
            }

            setStatus('Bu cihaz icin push bildirimi kapatildi.');
        } catch (error) {
            const message = error?.response?.data?.message || error?.message || 'Bildirim kapatilamadi.';
            setStatus(message, 'rose');
        } finally {
            setBusy(false);
        }
    });
}

function getAppBaseUrl() {
    return (document.body?.dataset?.appBaseUrl || window.location.origin).replace(/\/$/, '');
}

function initPwaInstallControls() {
    const installButtons = document.querySelectorAll('[data-pwa-install]');
    const installStatus = document.querySelector('[data-pwa-install-status]');

    if (!installButtons.length || !installStatus) {
        return;
    }

    const setStatus = (message, tone = 'slate') => {
        installStatus.textContent = message;
        installStatus.className = `rounded-lg border px-3 py-2 text-sm ${
            tone === 'green'
                ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                : tone === 'rose'
                    ? 'border-rose-200 bg-rose-50 text-rose-700'
                    : 'border-slate-200 bg-slate-50 text-slate-600'
        }`;
    };

    installButtons.forEach((button) => {
        button.disabled = !window.deferredPwaPrompt;

        button.addEventListener('click', async () => {
            try {
                if (!window.deferredPwaPrompt) {
                    setStatus('Bu cihazda kurulum penceresi henuz hazir degil. Android Chrome veya Edge kullanin.', 'rose');
                    return;
                }

                window.deferredPwaPrompt.prompt();
                const choice = await window.deferredPwaPrompt.userChoice;

                if (choice.outcome === 'accepted') {
                    setStatus('Uygulama kurulum istegi gonderildi.', 'green');
                } else {
                    setStatus('Kurulum iptal edildi.');
                }

                window.deferredPwaPrompt = null;
                button.disabled = true;
            } catch (error) {
                setStatus(error?.message || 'Kurulum baslatilamadi.', 'rose');
            }
        });
    });
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; i += 1) {
        outputArray[i] = rawData.charCodeAt(i);
    }

    return outputArray;
}
