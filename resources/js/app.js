import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
window.deferredPwaPrompt = null;
const THEME_STORAGE_KEY = 'lms_theme';

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    initThemeToggle();
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
    initPushPromptModal();
    initPwaInstallControls();
    initNotificationReadLinks();
});

function initThemeToggle() {
    const root = document.documentElement;
    const toggles = document.querySelectorAll('[data-theme-toggle]');
    const storageTheme = window.localStorage.getItem(THEME_STORAGE_KEY);
    const systemPrefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches;
    const initialTheme = storageTheme || (systemPrefersDark ? 'dark' : 'light');

    const syncMetaThemeColor = (theme) => {
        const metaTheme = document.querySelector('meta[name="theme-color"]');

        if (metaTheme) {
            metaTheme.setAttribute('content', theme === 'dark' ? '#374151' : '#0f172a');
        }
    };

    const applyTheme = (theme) => {
        root.classList.toggle('dark', theme === 'dark');
        root.dataset.theme = theme;
        syncMetaThemeColor(theme);

        toggles.forEach((toggle) => {
            const label = toggle.querySelector('[data-theme-toggle-label]');
            const icon = toggle.querySelector('[data-theme-toggle-icon]');
            const pressed = theme === 'dark';

            toggle.setAttribute('aria-pressed', pressed ? 'true' : 'false');
            toggle.setAttribute('title', pressed ? 'Aydinlik temaya gec' : 'Koyu temaya gec');
            toggle.setAttribute('aria-label', pressed ? 'Aydinlik temaya gec' : 'Koyu temaya gec');

            if (label) {
                label.textContent = pressed ? 'Dark' : 'Light';
            }

            if (icon) {
                icon.innerHTML = pressed
                    ? '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M12 3a1 1 0 0 1 1 1v1.2a1 1 0 1 1-2 0V4a1 1 0 0 1 1-1Zm0 14.8a1 1 0 0 1 1 1V20a1 1 0 1 1-2 0v-1.2a1 1 0 0 1 1-1Zm8-5.8a1 1 0 0 1 1 1 1 1 0 0 1-1 1h-1.2a1 1 0 1 1 0-2H20ZM5.2 12a1 1 0 1 1 0 2H4a1 1 0 1 1 0-2h1.2Zm11.75-5.95a1 1 0 0 1 1.41 1.41l-.85.85a1 1 0 0 1-1.41-1.41l.85-.85Zm-9.1 9.1a1 1 0 0 1 1.41 1.41l-.85.85A1 1 0 0 1 7 15.99l.85-.84Zm9.1 2.26a1 1 0 0 1-1.41 0l-.85-.85a1 1 0 1 1 1.41-1.41l.85.85a1 1 0 0 1 0 1.41ZM8.7 8.7a1 1 0 0 1-1.41 0l-.85-.85a1 1 0 1 1 1.41-1.41l.85.85A1 1 0 0 1 8.7 8.7ZM12 8a4 4 0 1 1 0 8 4 4 0 0 1 0-8Z"/></svg>'
                    : '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M20.742 13.045A8 8 0 0 1 10.955 3.258a1 1 0 0 0-1.31-1.247A10 10 0 1 0 22 14.355a1 1 0 0 0-1.258-1.31Z"/></svg>';
            }
        });
    };

    applyTheme(initialTheme);

    toggles.forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const nextTheme = root.classList.contains('dark') ? 'light' : 'dark';
            window.localStorage.setItem(THEME_STORAGE_KEY, nextTheme);
            applyTheme(nextTheme);
        });
    });
}

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
    const liveStateBox = document.querySelector('[data-push-live-state]');
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

    const setLiveState = (label, tone = 'slate') => {
        if (!liveStateBox) {
            return;
        }

        liveStateBox.textContent = label;
        liveStateBox.className = `inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold ${
            tone === 'green'
                ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                : tone === 'rose'
                    ? 'border-rose-200 bg-rose-50 text-rose-700'
                    : 'border-slate-200 bg-slate-100 text-slate-600'
        }`;
    };

    const syncDeviceStatus = async () => {
        const payload = await buildPushDeviceStatusPayload();

        if (!payload) {
            return;
        }

        try {
            await window.axios.post(`${baseUrl}/webpush/device-status`, payload);
        } catch (error) {
            // Device inventory sync is best-effort.
        }
    };

    const syncCurrentPushState = async () => {
        if (!('Notification' in window)) {
            setLiveState('Desteklenmiyor', 'rose');
            return;
        }

        if (Notification.permission === 'denied') {
            setLiveState('Tarayicida engelli', 'rose');
            return;
        }

        if (!('serviceWorker' in navigator)) {
            setLiveState('Desteklenmiyor', 'rose');
            return;
        }

        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();

            if (subscription) {
                setLiveState('Acik', 'green');
                if (countBox) {
                    countBox.dataset.count = '1';
                    countBox.textContent = '1 cihaz bagli';
                }
                await syncDeviceStatus();
                return;
            }

            setLiveState('Kapali');
            await syncDeviceStatus();
        } catch (error) {
            setLiveState('Kontrol edilemedi', 'rose');
        }
    };

    enableButton.addEventListener('click', async () => {
        setBusy(true);

        try {
            await enablePushForCurrentDevice();

            if (countBox) {
                countBox.dataset.count = '1';
                countBox.textContent = '1 cihaz bagli';
            }

            setStatus('Bu cihaz icin push bildirimi aktif.', 'green');
            setLiveState('Acik', 'green');
            await syncDeviceStatus();
        } catch (error) {
            const message = error?.response?.data?.message || error?.message || 'Bildirim acilamadi.';
            setStatus(message, 'rose');
            await syncCurrentPushState();
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
            setLiveState('Kapali');
            await syncDeviceStatus();
        } catch (error) {
            const message = error?.response?.data?.message || error?.message || 'Bildirim kapatilamadi.';
            setStatus(message, 'rose');
            await syncCurrentPushState();
        } finally {
            setBusy(false);
        }
    });

    syncCurrentPushState();
}

function initPushPromptModal() {
    const modal = document.querySelector('[data-push-prompt-modal]');
    const allowButton = document.querySelector('[data-push-prompt-allow]');
    const closeButton = document.querySelector('[data-push-prompt-close]');
    const laterButton = document.querySelector('[data-push-prompt-later]');
    const neverInput = document.querySelector('[data-push-prompt-never]');
    const statusBox = document.querySelector('[data-push-prompt-status]');
    const body = document.body;

    if (!modal || !allowButton || !laterButton || !closeButton || !neverInput || !statusBox || !body) {
        return;
    }

    const userId = body.dataset.authUserId || 'guest';
    const neverKey = `push_prompt_never_${userId}`;

    if (window.localStorage.getItem(neverKey) === '1') {
        return;
    }

    const support = getPushSupportDetails();
    const setStatus = (message, tone = 'slate') => {
        if (!message) {
            statusBox.textContent = '';
            statusBox.className = 'hidden rounded-2xl border px-4 py-3 text-sm';
            return;
        }

        statusBox.textContent = message;
        statusBox.className = `rounded-2xl border px-4 py-3 text-sm ${
            tone === 'green'
                ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                : tone === 'rose'
                    ? 'border-rose-200 bg-rose-50 text-rose-700'
                    : 'border-slate-200 bg-slate-50 text-slate-600'
        }`;
    };

    const closeModal = () => {
        if (neverInput.checked) {
            window.localStorage.setItem(neverKey, '1');
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
    };

    const openModal = () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
    };

    closeButton.addEventListener('click', closeModal);
    laterButton.addEventListener('click', closeModal);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    allowButton.addEventListener('click', async () => {
        allowButton.disabled = true;
        laterButton.disabled = true;

        try {
            if (!support.canSubscribe) {
                throw new Error(support.message);
            }

            await enablePushForCurrentDevice();
            setStatus('Bu cihaz icin push bildirimi aktif.', 'green');
            window.setTimeout(closeModal, 700);
        } catch (error) {
            setStatus(error?.response?.data?.message || error?.message || 'Bildirim acilamadi.', 'rose');
        } finally {
            allowButton.disabled = false;
            laterButton.disabled = false;
        }
    });

    const permission = window.Notification?.permission;

    if (permission === 'denied') {
        setStatus('Bildirim izni tarayicida engelli. Tarayici ayarlarindan bu site icin izni acabilirsiniz.', 'rose');
        allowButton.disabled = true;
        allowButton.classList.add('opacity-60', 'cursor-not-allowed');
        openModal();
        return;
    }

    if (!('serviceWorker' in navigator)) {
        if (!support.canSubscribe && support.message) {
            setStatus(support.message, 'rose');
            openModal();
        }

        return;
    }

    navigator.serviceWorker.ready
        .then((registration) => registration.pushManager.getSubscription())
        .then((subscription) => {
            if (subscription) {
                return;
            }

            if (!support.canSubscribe && !support.message) {
                return;
            }

            if (!support.canSubscribe) {
                setStatus(support.message, 'rose');
            }

            openModal();
        })
        .catch(() => {
            if (!support.canSubscribe && !support.message) {
                return;
            }

            if (!support.canSubscribe) {
                setStatus(support.message, 'rose');
            }

            openModal();
        });
}

async function enablePushForCurrentDevice() {
    const support = getPushSupportDetails();

    if (!support.canSubscribe) {
        throw new Error(support.message);
    }

    const permission = await Notification.requestPermission();
    if (permission !== 'granted') {
        throw new Error('Bildirim izni verilmedi.');
    }

    const baseUrl = getAppBaseUrl();
    const registration = await navigator.serviceWorker.ready;
    const publicKeyResponse = await window.axios.get(`${baseUrl}/webpush/public-key`);
    const publicKey = publicKeyResponse.data.publicKey;

    let subscription = await registration.pushManager.getSubscription();

    if (!subscription) {
        subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(publicKey),
        });
    }

    await window.axios.post(`${baseUrl}/webpush/subscribe`, {
        endpoint: subscription.endpoint,
        keys: subscription.toJSON().keys,
        contentEncoding: 'aes128gcm',
    });

    return subscription;
}

function getAppBaseUrl() {
    return (document.body?.dataset?.appBaseUrl || window.location.origin).replace(/\/$/, '');
}

async function buildPushDeviceStatusPayload() {
    const permissionState = 'Notification' in window ? Notification.permission : 'default';
    const platform = getPlatformType();
    const browser = getBrowserType();
    const deviceKey = getPushDeviceKey();
    let subscriptionEndpoint = null;

    if ('serviceWorker' in navigator) {
        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();
            subscriptionEndpoint = subscription?.endpoint || null;
        } catch (error) {
            subscriptionEndpoint = null;
        }
    }

    return {
        device_key: deviceKey,
        device_label: getPushDeviceLabel(platform, browser),
        platform,
        browser,
        user_agent: window.navigator.userAgent || '',
        permission_state: permissionState,
        subscription_endpoint: subscriptionEndpoint,
        is_standalone: isRunningStandalone(),
    };
}

function getPushDeviceKey() {
    const storageKey = 'push_device_key';
    const existing = window.localStorage.getItem(storageKey);

    if (existing) {
        return existing;
    }

    const generated = `device_${Math.random().toString(36).slice(2, 10)}${Date.now().toString(36)}`;
    window.localStorage.setItem(storageKey, generated);
    return generated;
}

function getPushDeviceLabel(platform, browser) {
    const platformLabels = {
        ios: 'iPhone / iPad',
        android: 'Android',
        windows: 'Windows',
        macos: 'macOS',
        linux: 'Linux',
        other: 'Diger',
    };
    const browserLabels = {
        chrome: 'Chrome',
        edge: 'Edge',
        firefox: 'Firefox',
        safari: 'Safari',
        opera: 'Opera',
        other: 'Tarayici',
    };

    return `${platformLabels[platform] || 'Cihaz'} ${browserLabels[browser] || 'Tarayici'} ${isRunningStandalone() ? 'PWA' : 'Web'}`;
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
