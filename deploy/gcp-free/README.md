# Free Deployment Plan for eWards PMS

This repository is a Laravel 12 + Vue/Inertia application, not a static Firebase site. The simplest fully free deployment path that fits the codebase without using Render is:

1. One Google Cloud Always Free Compute Engine VM
2. SQLite on the VM disk
3. Local file storage on the same VM disk
4. Nginx + PHP-FPM

## Why this path fits this repo

Project scan summary:

- Laravel backend with server-side auth and protected routes
- Vue 3 + Inertia frontend built by Vite
- SQLite is already the default database connection
- Sessions and cache already support database-backed storage
- Documents are uploaded to local Laravel storage
- Realtime code exists, but it is optional and can stay disabled on the free setup

This makes a single VM a better fit than Firebase Hosting or Firebase App Hosting for this specific project.

## Free-tier reality check

As of April 4, 2026:

- Google Cloud documentation says the Always Free tier includes 1 non-preemptible `e2-micro` VM per month in `us-west1`, `us-central1`, or `us-east1`, plus 30 GB-months of standard persistent disk and 1 GB/month outbound transfer from North America.
- Google Cloud documentation also says a billing account is still required to use the Free Tier.
- Firebase App Hosting documentation says App Hosting requires the Blaze plan.
- Firebase App Hosting framework docs describe Node.js runtimes and framework adapters, so Laravel/PHP is not a native fit there.

Official references:

- https://docs.cloud.google.com/free/docs/free-cloud-features
- https://firebase.google.com/docs/app-hosting/costs
- https://firebase.google.com/docs/app-hosting/frameworks-tooling
- https://firebase.google.com/docs/hosting

## Recommended production settings for the free setup

Use the included `.env.gcp-free.example` as the base for production:

- `DB_CONNECTION=sqlite`
- `SESSION_DRIVER=database`
- `CACHE_STORE=database`
- `QUEUE_CONNECTION=sync`
- `BROADCAST_CONNECTION=log`
- `FILESYSTEM_DISK=local`
- `MAIL_MAILER=log`

Those settings keep the stack free by avoiding Redis, managed queues, Pusher, and paid mail services.

## VM creation

Create a VM in Google Cloud with these choices:

1. Region: `us-central1`, `us-east1`, or `us-west1`
2. Machine type: `e2-micro`
3. OS: Ubuntu 24.04 LTS
4. Disk: standard persistent disk, stay within 30 GB
5. Firewall: allow HTTP, and allow HTTPS if you will add a domain later

If you exceed those limits, the deployment is no longer guaranteed to remain free.

## App bootstrap

Clone the repo onto the VM at `/var/www/ewards-pms`, then run:

```bash
chmod +x deploy/gcp-free/bootstrap-ubuntu.sh
APP_DIR=/var/www/ewards-pms ./deploy/gcp-free/bootstrap-ubuntu.sh
```

The script installs PHP, Node.js, Composer, Nginx, builds the frontend, runs migrations, and enables the included Nginx site config.

## Final manual steps

1. Edit `.env` and set `APP_URL` to your VM IP or real domain.
2. If you want demo data, run `php artisan db:seed --force`.
3. Open `http://YOUR_VM_IP/health` and confirm you get a healthy response.
4. Open the site and log in.

If you seed demo data, the seeded password is `password`. Change those credentials before sharing the app.

## What to keep disabled to stay free

- Leave Pusher keys empty so realtime is skipped
- Keep queue on `sync`
- Keep mail on `log`
- Keep uploads on local disk
- Do not add Cloud SQL, Redis, or third-party paid add-ons

## Known limitations of the free path

- You are running a single small VM, so performance is limited
- The Google Free Tier still requires a billing account
- Outbound traffic is limited, so a busy public app can exceed free limits
- If you delete the VM and its persistent disk, you lose the SQLite DB and uploaded files
- A custom domain itself is usually not free; using the raw VM IP is the no-cost option

## Why Firebase is not the recommended target for this repo

Firebase Hosting is mainly the right tool for static assets and Firebase-linked serverless backends. This repo is a Laravel monolith with:

- PHP runtime requirements
- SQLite persistence
- local uploaded documents
- database-backed sessions

You could force this through a container plus Cloud Run, but that is a weaker fit for the app's local persistence model and Firebase App Hosting is not a Spark-plan-only product.
