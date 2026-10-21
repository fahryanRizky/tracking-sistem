import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const notificationFeed = document.getElementById('notifications-feed');

if (notificationFeed) {
    const badge = document.getElementById('notifications-badge');
    const unreadCount = document.getElementById('notifications-unread-count');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    let refreshInProgress = false;

    const refreshNotifications = async () => {
        if (refreshInProgress || document.hidden) {
            return;
        }

        refreshInProgress = true;

        try {
            const response = await fetch(notificationFeed.dataset.feedUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store',
            });

            if (!response.ok) {
                throw new Error(`Notification refresh failed with status ${response.status}.`);
            }

            const data = await response.json();
            const count = Number(data.unread_count);
            const fragment = document.createDocumentFragment();

            data.notifications.forEach((notification) => {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = notification.read_url;

                const token = document.createElement('input');
                token.type = 'hidden';
                token.name = '_token';
                token.value = csrfToken;
                form.append(token);

                const button = document.createElement('button');
                button.type = 'submit';
                button.className = `block w-full border-b border-gray-100 px-4 py-3 text-left text-sm hover:bg-gray-50 ${
                    notification.read ? 'bg-white text-gray-700' : 'bg-yellow-500 text-gray-950'
                }`;

                const message = document.createElement('span');
                message.className = 'block';
                message.textContent = notification.message;

                const timestamp = document.createElement('span');
                timestamp.className = 'mt-1 block text-xs opacity-75';
                timestamp.textContent = notification.created_at;

                button.append(message, timestamp);
                form.append(button);
                fragment.append(form);
            });

            if (data.notifications.length === 0) {
                const emptyMessage = document.createElement('p');
                emptyMessage.className = 'px-4 py-6 text-center text-sm text-gray-500';
                emptyMessage.textContent = 'Belum ada notifikasi.';
                fragment.append(emptyMessage);
            }

            notificationFeed.replaceChildren(fragment);
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.hidden = count === 0;
            unreadCount.textContent = `${count} belum dibaca`;
        } finally {
            refreshInProgress = false;
        }
    };

    const refreshAndReportErrors = () => {
        refreshNotifications().catch((error) => {
            console.error('Gagal memperbarui notifikasi.', error);
        });
    };

    refreshAndReportErrors();
    window.setInterval(refreshAndReportErrors, 2000);
    document.addEventListener('visibilitychange', refreshAndReportErrors);
    window.addEventListener('focus', refreshAndReportErrors);
}
