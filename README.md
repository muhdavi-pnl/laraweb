# Academic API

Backend REST API (Laravel 13, JWT) for authentication, student data, and lecturer data.

```sh
composer setup          # install, .env, key, migrate
php artisan jwt:secret  # generate JWT_SECRET
php artisan migrate:fresh --seed
php artisan serve
composer test
vendor/bin/pint --test   # code style (also run in CI)
```

API docs: `/docs` (Scribe), OpenAPI spec in `docs/openapi.yaml`.

Seeded accounts (password `password`): `admin@example.com`, `lecturer@example.com`, `student@example.com`.
