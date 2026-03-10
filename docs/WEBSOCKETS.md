# Real-time updates (WebSockets)

The app uses **Laravel Reverb** and **Laravel Echo** for real-time updates. When enabled, other users see changes immediately when:

- **Web customization** is saved (theme, colors, logo, etc.) — all sessions on that tenant reload to show the new look.
- **RBAC / role permissions** are updated (Super Admin or Barangay Admin) — sessions reload so permission changes take effect without a manual refresh.

## Setup

### 1. Environment

In `.env` set:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

For local development you can use any non-empty values for the app id/key/secret (e.g. `reverb-app-id`, `reverb-key`, `reverb-secret`). `REVERB_HOST` should be the host the browser uses to connect (e.g. `localhost`). `REVERB_PORT` must match the port Reverb listens on (default `8080`).

### 2. Start the Reverb server

In a separate terminal:

```bash
php artisan reverb:start
```

Leave this running while you need real-time updates.

### 3. Run the queue worker

Broadcast events are queued. Start a worker so they are sent:

```bash
php artisan queue:work
```

(Or use `php artisan queue:listen` if you prefer.)

### 4. Frontend

The frontend is already configured: when `BROADCAST_CONNECTION=reverb` and Reverb config is present, the layout injects `reverbConfig` and `resources/js/bootstrap.js` initializes Laravel Echo. No extra build step is required; run `npm run dev` or `npm run build` as usual.

## Disabling real-time

Set `BROADCAST_CONNECTION=log` or `null` in `.env`. The app works normally; users will need to refresh to see RBAC or customization changes.
