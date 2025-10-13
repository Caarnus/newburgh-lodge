# Newburgh Lodge #174 Website

A modern Laravel + Inertia/Vue site for **Newburgh Masonic Lodge No. 174 (Indiana)** — originally chartered **May 29, 1855**, with the current building’s cornerstone set **April 21, 1962**. This repository contains the application powering the Lodge’s public website and admin tools.

> Stack highlights: **Laravel** backend with **Inertia + Vue 3** on the front-end, built by **Vite**, styled with **Tailwind** and **PrimeVue (unstyled)**. The repo includes config and build files such as `vite.config.js`, `tailwind.config.js`, and `docker-compose.yml`, and lists languages primarily **Vue, CSS, PHP**. :contentReference[oaicite:0]{index=0}

---

## ✨ Features (current & in-progress)

- **Tile-based homepage** (image/text/news modules) for quick content updates.
- **“Compass Points” newsletter** module.
- **Role-based access control** for officers/admins.
- **Contact form** & map/directions to the Lodge.
- **Events & calendar** groundwork for stated meetings and special events.

> Note: The repo is an active work-in-progress; feature availability evolves with commits.

---

## 🧱 Architecture

- **Laravel** app as the backend/API and server-rendered entry.  
- **Inertia.js** bridges Laravel routes to SPA pages.  
- **Vue 3** front-end with components under `resources/`.  
- **Vite** for dev server & production builds (see `vite.config.js`). :contentReference[oaicite:1]{index=1}  
- **Tailwind CSS** + **PrimeVue (unstyled)** for UI components (see `tailwind.config.js`, `package.json`). :contentReference[oaicite:2]{index=2}  
- Optional local services via **`docker-compose.yml`**. :contentReference[oaicite:3]{index=3}

---

## 🧰 Requirements

- **PHP 8.3+**, **Composer 2+**  
- **Node 20+** (or 22+) & **npm**  
- **MySQL 8.0+** (default DB)  
- **Git**  
- (Optional) Docker Desktop if you prefer containers.

---

## 🚀 Quick Start (local)

```bash
# 1) Clone
git clone https://github.com/Caarnus/newburgh-lodge.git
cd newburgh-lodge

# 2) PHP deps
composer install

# 3) Node deps
npm install

# 4) Environment
cp .env.example .env

# 5) Set APP_URL and MySQL credentials in .env (see "Env Vars" below)

# 6) App key + storage link
php artisan key:generate
php artisan storage:link

# 7) Database
php artisan migrate --force   # add --seed if you have seeders ready

# 8) Run dev servers (two terminals or with &)
php artisan serve
npm run dev
````

Visit the app at `http://localhost:8000` (or whatever your `php artisan serve` prints).

---

## ⚙️ Environment Variables (minimum)

| Key                 | Example                         | Notes                              |
| ------------------- | ------------------------------- | ---------------------------------- |
| `APP_NAME`          | `Newburgh Lodge #174`           | Display name                       |
| `APP_ENV`           | `local`                         | `local`, `staging`, `production`   |
| `APP_URL`           | `http://localhost:8000`         | Must match where the app is served |
| `APP_DEBUG`         | `true`                          | `false` in production              |
| `DB_CONNECTION`     | `mysql`                         | **Use MySQL**                      |
| `DB_HOST`           | `127.0.0.1`                     |                                    |
| `DB_PORT`           | `3306`                          | Default MySQL port                 |
| `DB_DATABASE`       | `newburgh_lodge`                | Create this db first               |
| `DB_USERNAME`       | `root`                          |                                    |
| `DB_PASSWORD`       | `secret`                        |                                    |
| `MAIL_MAILER`       | `smtp`                          |                                    |
| `MAIL_HOST`         | `smtp.mailtrap.io`              | or your SMTP                       |
| `MAIL_PORT`         | `2525`                          |                                    |
| `MAIL_USERNAME`     | `…`                             |                                    |
| `MAIL_PASSWORD`     | `…`                             |                                    |
| `MAIL_FROM_ADDRESS` | `no-reply@newburghlodge174.org` |                                    |
| `MAIL_FROM_NAME`    | `Newburgh Lodge #174`           |                                    |

> Database is **MySQL** per project direction.

---

## 🧪 NPM & Artisan scripts

Common scripts you’ll use:

```bash
# Dev server (Vite)
npm run dev

# Production build
npm run build

# Basic test suites (if added)
php artisan test
```

---

## 📦 Optional: Docker

If you prefer containers for local services (e.g., MySQL), the repo includes a `docker-compose.yml`. Adjust services/ports as needed, then:

```bash
docker compose up -d
# update .env DB_HOST, DB_PORT, DB_USERNAME, DB_PASSWORD to match the compose services
```

([GitHub][1])

---

## 📤 Deployment

This project follows a standard Laravel deployment flow:

1. **Build & dependencies** on the server (or in CI):
   `composer install --no-dev --prefer-dist --optimize-autoloader`
   `npm ci && npm run build`
2. **App optimizations:**
   `php artisan optimize && php artisan storage:link`
3. **Migrations:**
   `php artisan migrate --force`
4. **Reload PHP-FPM** (if applicable) and clear caches when needed.

A `deploy.sh` exists in the repo; you can model your provider (e.g., Forge) steps after it, adapting branch/environment specifics. ([GitHub][1])

---

## 🔐 Roles & Access

* Officers/admins get access to content tools (tiles, newsletter, etc.).
* Standard members may have limited access areas as features roll out.

(If you’re using `spatie/laravel-permission`, seed roles/permissions accordingly.)

---

## 🧭 Project Context

* Organization: **Newburgh Masonic Lodge No. 174 (Newburgh, IN)**
* Public site: [https://newburghlodge174.org](https://newburghlodge174.org)
* Purpose: present Lodge information, news (“Compass Points”), and event details; streamline content management for officers.

---

## 🛠 Troubleshooting

* **White screen / route not updating during dev**
  Kill and restart `npm run dev` and `php artisan serve`. Ensure `APP_URL` matches the URL you’re visiting.
* **Permission errors writing to `storage`/`bootstrap/cache`**
  Fix ownership/permissions so PHP can write caches/uploads.
* **Vite assets not loading in prod**
  Run `npm run build` and ensure `php artisan optimize:clear` after deployments.
* **DB connection failures**
  Verify `.env` matches your MySQL host/port/user/password and that the DB exists.

---

## 👥 Contributing

PRs and issues are welcome. Please keep PRs focused and include a brief description, screenshots (if UI), and steps to test.

---

## 📄 License

No license file is present at the moment; assume standard copyright / all rights reserved to the Lodge unless a license is added.

---

## 📚 Useful Repo Pointers

* Root shows **`README.md`**, `vite.config.js`, `tailwind.config.js`, `docker-compose.yml`, `.env.example`, and the usual Laravel directories (`app`, `routes`, `resources`, etc.).
  See the repo’s root listing and “Languages” section for confirmation of the stack. ([GitHub][1])


[1]: https://github.com/Caarnus/newburgh-lodge "GitHub - Caarnus/newburgh-lodge"
