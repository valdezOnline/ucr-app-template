# UCR App Template

This repository is a Laravel application migrated to a modular architecture with `Core` as the base module.

All documentation is consolidated under [docs/](/home/sudo-joelv/dev-drive/my-sandbox/app-template/docs).

## Start Here

- [Documentation Index](./docs/README.md)
- [Setup Guide](./docs/setup.md)
- [Modular Architecture](./docs/modular-architecture.md)
- [Authentication Guide](./docs/authentication-guide.md)
- [API Authentication Guide](./docs/api-authentication-guide.md)
- [User Management Guide](./docs/user-management.md)

## Common Commands

```bash
composer install
php artisan key:generate
php artisan migrate
npm install
npm run dev
```

## Module Commands

```bash
php artisan module:list
php artisan module:make Billing
php artisan module:make Billing --email-template=welcome-user
```
