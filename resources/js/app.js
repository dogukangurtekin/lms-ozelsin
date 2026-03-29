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
    initNotificationReadLinks();
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
    const support = getPushSupportDetails();

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
            if (!support.canSubscribe) {
                throw new Error(support.message);
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
    const installDialog = document.getElementById('pwa-install-dialog');
    const installDialogTitle = document.querySelector('[data-pwa-install-dialog-title]');
    const installDialogBody = document.querySelector('[data-pwa-install-dialog-body]');
    const install = getInstallSupportDetails();

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

    if (install.isStandalone) {
        installButtons.forEach((button) => {
            button.disabled = true;
        });
        setStatus('Uygulama zaten bu cihaza kurulu gorunuyor.', 'green');
        return;
    }

    if (install.manualOnly) {
        installButtons.forEach((button) => {
            button.disabled = false;
        });
        setStatus(install.message);
    }

    installButtons.forEach((button) => {
        button.disabled = install.manualOnly ? false : !window.deferredPwaPrompt;

        button.addEventListener('click', async () => {
            try {
                if (install.manualOnly) {
                    setStatus(install.message);
                    showInstallHelpDialog(installDialog, installDialogTitle, installDialogBody, install);
                    return;
                }

                if (!window.deferredPwaPrompt) {
                    setStatus(install.message || 'Bu cihazda kurulum penceresi henuz hazir degil.', 'rose');
                    showInstallHelpDialog(installDialog, installDialogTitle, installDialogBody, install);
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

function showInstallHelpDialog(dialog, titleNode, bodyNode, install) {
    if (!dialog || !titleNode || !bodyNode) {
        return;
    }

    titleNode.textContent = install.title || 'Kurulum Yardimi';
    bodyNode.innerHTML = '';

    const intro = document.createElement('p');
    intro.className = 'text-sm text-slate-600';
    intro.textContent = install.message || 'Bu cihaz icin uygun kurulum adimlarini izleyin.';
    bodyNode.appendChild(intro);

    if (Array.isArray(install.steps) && install.steps.length) {
        const list = document.createElement('ol');
        list.className = 'mt-4 list-decimal space-y-2 pl-5 text-sm text-slate-700';

        install.steps.forEach((step) => {
            const item = document.createElement('li');
            item.textContent = step;
            list.appendChild(item);
        });

        bodyNode.appendChild(list);
    }

    dialog.showModal();
}

function isIosDevice() {
    const userAgent = window.navigator.userAgent || '';
    const platform = window.navigator.platform || '';

    return /iPad|iPhone|iPod/.test(userAgent)
        || (platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);
}

function isRunningStandalone() {
    return window.matchMedia?.('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
}

function getInstallSupportDetails() {
    const isStandalone = isRunningStandalone();
    const platform = getPlatformType();
    const browser = getBrowserType();

    if (isStandalone) {
        return { isStandalone: true, manualOnly: false, message: '' };
    }

    if (platform === 'ios') {
        return {
            isStandalone: false,
            manualOnly: true,
            title: 'iPhone ve iPad Kurulumu',
            message: 'iPhone ve iPad kurulumu Paylas menusu ile yapilir. Safari, Chrome, Edge veya Firefox icinde Paylas > Ana Ekrana Ekle adimlarini kullanin.',
            steps: [
                'Siteyi iPhone veya iPad tarayicisinda acin.',
                'Paylas dugmesine dokunun.',
                'Ana Ekrana Ekle secenegini secin.',
                'Kurulan simgeden uygulamayi acin.',
                'Ardindan Bildirimleri Ac butonunu kullanin.',
            ],
        };
    }

    if (platform === 'macos' && browser === 'safari') {
        return {
            isStandalone: false,
            manualOnly: true,
            title: 'macOS Safari Kurulumu',
            message: 'macOS Safari icinde uygulamayi kurmak icin Dosya > Docka Ekle secenegini kullanin.',
            steps: [
                'Siteyi Safari ile acin.',
                'Ust menuden Dosya secenegine girin.',
                'Docka Ekle secenegini secin.',
                'Kurulan uygulamayi Docktan acin.',
            ],
        };
    }

    if ((platform === 'windows' || platform === 'linux' || platform === 'android' || platform === 'macos') && browser === 'firefox') {
        return {
            isStandalone: false,
            manualOnly: true,
            title: 'Firefox Kurulum Bilgisi',
            message: 'Firefox bildirimleri destekler ancak PWA kurulum penceresi sunmaz. Kurulum icin Chrome veya Edge kullanin.',
            steps: [
                'Ayni siteyi Chrome veya Edge ile acin.',
                'Adres cubugundaki kurulum simgesini veya tarayici menusundeki uygulama kur secenegini kullanin.',
                'Kurulumdan sonra yeni uygulama kisayolunu acin.',
            ],
        };
    }

    if (platform === 'android') {
        return {
            isStandalone: false,
            manualOnly: false,
            title: 'Android Kurulumu',
            message: 'Android tarayiciniz destekliyorsa bu buton kurulum penceresini acar.',
            steps: [
                'Butona dokunun ve gelen kurulum penceresini onaylayin.',
                'Pencere acilmazsa tarayici menusunden Ana Ekrana Ekle veya Uygulamayi Yukle secenegini kullanin.',
            ],
        };
    }

    if (platform === 'windows' || platform === 'linux') {
        return {
            isStandalone: false,
            manualOnly: false,
            title: 'Windows ve Linux Kurulumu',
            message: 'Chrome ve Edge bu butonla kurulum penceresi acabilir.',
            steps: [
                'Butona basin ve kurulum penceresini onaylayin.',
                'Pencere acilmazsa adres cubugundaki kurulum simgesini kontrol edin.',
                'Firefox kullaniyorsaniz kurulum icin Chrome veya Edge kullanin.',
            ],
        };
    }

    if (platform === 'macos') {
        return {
            isStandalone: false,
            manualOnly: false,
            title: 'macOS Kurulumu',
            message: 'Chrome ve Edge bu butonla kurulum penceresi acabilir. Safari ise Dosya menusu ile kurulum yapar.',
            steps: [
                'Chrome veya Edge kullaniyorsaniz butona basin ve kurulum penceresini onaylayin.',
                'Safari kullaniyorsaniz Dosya > Docka Ekle yolunu kullanin.',
            ],
        };
    }

    return {
        isStandalone: false,
        manualOnly: false,
        title: 'Kurulum Yardimi',
        message: 'Bu cihazda otomatik kurulum penceresi henuz hazir degil.',
        steps: [
            'Siteyi destekli bir tarayicida acin.',
            'Tarayici menusu veya adres cubugundaki kurulum secenegini kullanin.',
        ],
    };
}

function getPushSupportDetails() {
    const platform = getPlatformType();
    const browser = getBrowserType();
    const hasBaseSupport = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

    if (platform === 'ios') {
        if (!hasBaseSupport) {
            return {
                canSubscribe: false,
                message: 'Bu iPhone veya iPad kombinasyonunda web push kullanilamiyor. iOS 16.4+ ve guncel Safari tabanli tarayici gerekli.',
            };
        }

        if (!isRunningStandalone()) {
            return {
                canSubscribe: false,
                message: 'iPhone ve iPad icin push bildirimi ancak Ana Ekrana eklenmis web uygulamasinda calisir.',
            };
        }

        return { canSubscribe: true, message: '' };
    }

    if (hasBaseSupport) {
        return { canSubscribe: true, message: '' };
    }

    if (platform === 'macos' && browser === 'firefox') {
        return {
            canSubscribe: false,
            message: 'macOS Firefox bu kurulumda push icin uygun degil. Safari veya Chrome/Edge kullanin.',
        };
    }

    if (platform === 'windows' || platform === 'linux') {
        return {
            canSubscribe: false,
            message: 'Windows ve Linux icin Chrome, Edge veya Firefox uzerinden bildirim izni verin.',
        };
    }

    if (platform === 'android') {
        return {
            canSubscribe: false,
            message: 'Android icin Chrome, Edge, Firefox, Opera veya Samsung Internet kullanin.',
        };
    }

    if (platform === 'macos') {
        return {
            canSubscribe: false,
            message: 'macOS icin Safari, Chrome veya Edge uzerinden bildirim izni verin.',
        };
    }

    return {
        canSubscribe: false,
        message: 'Bu tarayici push bildirimi desteklemiyor.',
    };
}

function getPlatformType() {
    const userAgent = window.navigator.userAgent || '';
    const platform = window.navigator.platform || '';

    if (isIosDevice()) return 'ios';
    if (/Android/i.test(userAgent)) return 'android';
    if (/Win/i.test(platform)) return 'windows';
    if (/Mac/i.test(platform)) return 'macos';
    if (/Linux/i.test(platform)) return 'linux';

    return 'other';
}

function getBrowserType() {
    const userAgent = window.navigator.userAgent || '';

    if (/Firefox\//i.test(userAgent)) return 'firefox';
    if (/Edg\//i.test(userAgent)) return 'edge';
    if (/OPR\//i.test(userAgent) || /Opera\//i.test(userAgent)) return 'opera';
    if (/Chrome\//i.test(userAgent) || /CriOS\//i.test(userAgent)) return 'chrome';
    if (/Safari\//i.test(userAgent)) return 'safari';

    return 'other';
}

function initNotificationReadLinks() {
    const links = document.querySelectorAll('[data-notification-read-url]');

    if (!links.length) {
        return;
    }

    links.forEach((link) => {
        link.addEventListener('click', () => {
            const readUrl = link.dataset.notificationReadUrl;

            if (!readUrl) {
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (navigator.sendBeacon && csrfToken) {
                const payload = new FormData();
                payload.append('_token', csrfToken);
                navigator.sendBeacon(readUrl, payload);
                return;
            }

            window.fetch(readUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            }).catch(() => {});
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
