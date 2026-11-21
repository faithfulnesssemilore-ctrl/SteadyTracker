import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

if (typeof window !== 'undefined') {
    window.Pusher = Pusher;

    const echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT,
        forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
    });

    window.Echo = echo;

    echo.connector.pusher.connection.bind(
        'state_change',
        ({ previous, current }) => {
            if (current === 'connected' && previous !== 'connected') {
                window.dispatchEvent(new Event('steady:reconnected'));
            }
        },
    );
}
