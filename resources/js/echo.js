import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const accessToken = sessionStorage.getItem('hotel_task_access_token');

if (accessToken && document.getElementById('task-tracker')) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
        authorizer: channel => ({
            authorize: (socketId, callback) => {
                const body = new URLSearchParams({ socket_id: socketId, channel_name: channel.name });

                fetch('/api/broadcasting/auth', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        Authorization: `Bearer ${accessToken}`,
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body,
                })
                    .then(async response => {
                        const data = await response.json();
                        if (!response.ok) {
                            throw new Error(data.message || 'Channel authorization failed.');
                        }
                        callback(null, data);
                    })
                    .catch(error => callback(error, {}));
            },
        }),
    });

    window.dispatchEvent(new CustomEvent('hotel-task-echo-ready', { detail: { echo: window.Echo } }));
}
