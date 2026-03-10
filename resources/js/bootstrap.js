import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Laravel Echo (WebSockets) for real-time updates — enabled when Reverb config is present
if (typeof window.reverbConfig !== 'undefined' && window.reverbConfig.key) {
    import('pusher-js').then(({ default: Pusher }) => {
        import('laravel-echo').then(({ default: Echo }) => {
            window.Pusher = Pusher;
            window.Echo = new Echo({
                broadcaster: 'reverb',
                key: window.reverbConfig.key,
                wsHost: window.reverbConfig.host,
                wsPort: window.reverbConfig.port,
                wssPort: window.reverbConfig.port,
                forceTLS: (window.reverbConfig.scheme || 'http') === 'https',
                enabledTransports: ['ws', 'wss'],
                disableStats: true,
            });
            window.dispatchEvent(new Event('echo-ready'));
        });
    });
}
