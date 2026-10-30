<<<<<<< HEAD
# HotelTask

HotelTask is a Laravel and MySQL task management application for multiple hotel properties. The API uses Laravel Sanctum bearer tokens; property, department, role, approval, task, and report access is checked on the server.

## Requirements

- PHP 8.3 or newer with the extensions required by Laravel
- Composer
- MySQL 8 or a compatible MySQL server
- Node.js and npm

## Local setup

1. Copy `.env.example` to `.env` and set the database and mail settings.
2. Install dependencies and create the application key:

   ```sh
   composer install
   php artisan key:generate
   npm install
   ```

3. Run migrations and local sample seed data:

   ```sh
   php artisan migrate --seed
   ```

   Seeding is limited to local and test environments. The local admin password comes from `SEED_USER_PASSWORD`; use a password with at least 12 characters.

4. Build assets and start Laravel:

   ```sh
   npm run build
   php artisan serve
   ```

   During frontend development, use `npm run dev` instead of the production asset build.

## First administrator

Production seeding is intentionally disabled, and migrations do not grant an administrator role based on an email address. After deploying the migrations, create the first system administrator interactively:

```sh
php artisan app:create-admin
```

The command refuses to create a second system administrator and reads the password without displaying it.
The reserved local seed address `admin@local.test` is never granted system administrator privileges outside local and test environments.

## Email

Configure a working `MAIL_MAILER`, SMTP host, credentials, and sender address before accepting registrations. Registration is saved even if an email provider is temporarily unavailable; the API response reports whether the notification was sent, and the failure is logged.

## Checks

```sh
php artisan test
vendor/bin/pint --test
npm run build
```

The dashboard refreshes task analytics every 30 seconds. This is periodic polling, not a WebSocket push connection.
=======
# hoteltaskmanager
>>>>>>> 4ed1364cfa2f44b0a5ffb8163b97f11e401f3c66
