# TrendHub

All your trending stories, in one place. TrendHub aggregates what's trending across **dev.to**, **Hacker News**, **Stack Overflow**, **Product Hunt**, **Lobsters**, and **Mastodon** into a single ranked feed.

## Features

- Unified feed ranked by a normalized 0–100 trending score per source
- Sidebar toggle to enable/disable individual platforms per user
- Tag, time-range, and search filtering (matches titles and tags)
- Cross-platform duplicate detection ("also trending on...")
- Trending velocity indicator ("🔥 rising")
- Bookmarks and hide/dismiss per post
- Daily digest email (opt-in/out)
- Personal RSS feed export
- Keyboard shortcuts (`j`/`k` navigate, `o` open, `b` bookmark, `x` hide)
- Dark mode toggle
- Per-post share menu (X, Facebook, LinkedIn, Reddit, WhatsApp, email, copy link)
- Source health panel (last fetch time/status per platform)

## Stack

Laravel 10 + Inertia.js + Vue 3 + Tailwind CSS, SQLite for local dev, database-backed queue for fetch jobs.

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm run build
```

## Pulling trending data

```bash
php artisan app:fetch-trending          # dispatches a fetch job for every configured source
php artisan queue:work                  # processes the jobs
```

The scheduler (`app/Console/Kernel.php`) runs these automatically on a cadence per source — wire up `php artisan schedule:work` (or a cron entry calling `schedule:run` every minute) to keep it running unattended. It also sends the daily digest email at 08:00.

### Optional sources

- **Product Hunt** needs a free API token: set `PRODUCTHUNT_TOKEN` in `.env`.
- **Mastodon** defaults to `mastodon.social`; override with `MASTODON_INSTANCE`.

## Tests

```bash
php artisan test
```
