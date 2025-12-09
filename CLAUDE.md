# CLAUDE.md - Development Guide

## Project Overview

RealWorld example app (Medium.com clone) built with vanilla PHP 8.3 and boosti.js (a HTMX subset). File-based routing with `[param]` syntax.

## Quick Start

```bash
make setup     # Install dependencies + create SQLite database
make serve     # Start Docker container at http://localhost:8082
make seed      # Populate with test data from database/data/seed.yaml
make clean     # Reset database
bun run build  # Build CSS/JS assets with Vite (hashed for cachebusting)
```

## Architecture

### Directory Structure

```text
app/
├── components/            # Reusable UI components
│   ├── article-meta/      # Author avatar, name, date
│   │   ├── controller.php # Logic and data preparation
│   │   └── template.php   # Pure HTML presentation
│   ├── article-preview/   # Article card for lists
│   ├── comment/           # Comment card with delete
│   ├── favorite-button/   # Favorite/unfavorite button
│   └── follow-button/     # Follow/unfollow button
├── pages/                 # File-based routing (URL = file path)
│   ├── index.php          # GET /
│   ├── login.php          # GET/POST /login
│   ├── register.php       # GET/POST /register
│   ├── settings.php       # GET/POST /settings
│   ├── editor/
│   │   ├── index.php      # GET/POST /editor (new article)
│   │   └── [slug].php     # GET/POST /editor/{slug} (edit article)
│   ├── article/
│   │   └── [slug]/
│   │       ├── index.php      # GET /article/{slug}
│   │       ├── delete.php     # POST /article/{slug}/delete
│   │       ├── favorite.php   # POST /article/{slug}/favorite
│   │       ├── unfavorite.php # POST /article/{slug}/unfavorite
│   │       ├── comments.php   # POST /article/{slug}/comments
│   │       └── comment/
│   │           └── [id].php   # DELETE /article/{slug}/comment/{id}
│   └── profile/
│       └── [username]/
│           ├── index.php      # GET /profile/{username}
│           ├── follow.php     # POST /profile/{username}/follow
│           └── unfollow.php   # POST /profile/{username}/unfollow
├── lib/                   # Framework core
│   ├── Router.php         # File-based router with [param] support
│   ├── Database.php       # Doctrine DBAL singleton
│   ├── Auth.php           # JWT authentication
│   ├── Security.php       # CSRF protection
│   ├── View.php           # Template rendering + component support
│   └── Config.php         # Environment configuration
├── models/                # Data models
│   ├── User.php
│   ├── Article.php
│   └── Comment.php
├── services/              # Business logic services
│   └── Seeder.php         # Database seeding service
├── templates/             # PHP view templates
│   └── layout.php
└── public/                # Web root
    ├── index.php          # Entry point
    ├── dist/              # Vite build output (hashed assets)
    ├── fonts/
    └── img/

resources/                 # Source assets (pre-build)
├── css/
│   ├── fonts.css          # @font-face declarations
│   ├── icons.css          # Ionicons via CSS masks
│   └── main.css           # Bootstrap + Conduit styles
└── js/
    ├── app.js             # Entry point (imports CSS + JS)
    ├── boosti.min.js      # HTMX & SPA navigation library
    └── yolomode.min.js    # Link prefetching

database/                  # Database infrastructure
├── database.sqlite        # SQLite database (default)
├── schema.sql             # SQLite schema
├── schema-mysql.sql       # MySQL schema
├── schema-postgres.sql    # PostgreSQL schema
├── seed.php               # Database seeder
└── data/seed.yaml         # Test data
```

### File-Based Routing

Routes are determined by file paths in `app/pages/`:

| File                                  | Route                        |
| ------------------------------------- | ---------------------------- |
| `pages/index.php`                     | `/`                          |
| `pages/login.php`                     | `/login`                     |
| `pages/article/[slug]/index.php`      | `/article/{slug}`            |
| `pages/profile/[username]/follow.php` | `/profile/{username}/follow` |

Parameters in brackets become variables: `[slug].php` → `$slug` is available.

### Page File Structure

Each page handles its own HTTP methods:

```php
<?php
use App\Lib\Auth;
use App\Lib\View;
use App\Models\Article;

match ($request->method) {
    'GET' => showPage(),
    'POST' => handleSubmit(),
    default => abort(405),
};

function showPage(): void {
    View::renderLayout('template', ['data' => $value]);
}
```

The `$request` object provides: `method`, `params`, `query`, `body`, `isFixi`.

### Components

Reusable UI components in `app/components/`. Each has:

- `controller.php` - Logic, data preparation, calls View::component()
- `template.php` - Pure HTML with simple control flow only

```php
// Controller pattern
namespace App\Components\FavoriteButton;

use App\Lib\View;

function render(array $article, bool $isFavorited): void
{
    $props = [
        'slug' => $article['slug'],
        'count' => $article['favoritesCount'],
        'buttonClass' => $isFavorited ? 'btn-primary' : 'btn-outline-primary',
        // ... all computed values
    ];
    View::component(__DIR__ . '/template.php', $props);
}
```

```php
// Usage in templates
\App\Components\FavoriteButton\render($article, $isFavorited);
\App\Components\Comment\render($comment, $articleSlug);
\App\Components\ArticlePreview\render($article);
```

### Core Components

- **Router** (`app/lib/Router.php`): File-based routing with `[param]` syntax. Auto-validates CSRF on non-GET.
- **Database** (`app/lib/Database.php`): Doctrine DBAL singleton with SQLite optimizations.
- **Auth** (`app/lib/Auth.php`): JWT-based authentication via httpOnly cookies.
- **Security** (`app/lib/Security.php`): CSRF protection using HMAC-derived tokens.
- **View** (`app/lib/View.php`): Template rendering with layout support and `View::component()` for components.
- **Vite** (`app/lib/Vite.php`): Asset helper for Vite manifest resolution and critical CSS injection.

## Database

Default: SQLite at `database/database.sqlite`

Configure via `.env`:

```env
DB_DRIVER=pdo_sqlite|pdo_mysql|pdo_pgsql
DB_PATH=database/database.sqlite  # SQLite only
DB_HOST=localhost                # MySQL/PostgreSQL
DB_PORT=3306
DB_NAME=realworld
DB_USER=root
DB_PASSWORD=
```

Tables: users, articles, tags, article_tags, comments, favorites, follows

## Key Patterns

### Adding a New Page

Create a file in `app/pages/`:

```php
// app/pages/about.php -> GET /about
<?php
use App\Lib\View;

View::renderLayout('about', ['title' => 'About Us']);
```

### Adding a Parameterized Route

```php
// app/pages/user/[id].php -> GET /user/{id}
<?php
use App\Models\User;

$user = User::findById($id);  // $id is extracted from URL
// ...
```

### Adding a Component

Create `app/components/{name}/controller.php` and `template.php`:

```php
// controller.php
namespace App\Components\MyComponent;

use App\Lib\View;

function render(array $data): void
{
    $props = [
        'title' => $data['title'],
        'formattedDate' => date('F jS', strtotime($data['date'])),
    ];
    View::component(__DIR__ . '/template.php', $props);
}
```

```php
// template.php (pure HTML, no logic)
<div class="my-component">
    <h2><?= htmlspecialchars($title) ?></h2>
    <span><?= $formattedDate ?></span>
</div>
```

Add to `app/composer.json`:

```json
"files": [
    "components/my-component/controller.php"
]
```

Run `composer dump-autoload`.

### Model Queries

```php
$db = Database::getConnection();
$result = $db->fetchAssociative($sql, $params);
$db->insert('table', ['column' => 'value']);
```

### boosti.js Integration

boosti.js is a fork of fixi.js (which itself is a barebones version of htmx) with boost, confirm, and reset extensions (~4KB).

**Attributes:**

- `fx-action` - URL endpoint (required for AJAX)
- `fx-method` - HTTP method (default: GET)
- `fx-target` - CSS selector for swap target
- `fx-swap` - Swap mode: outerHTML, innerHTML, afterbegin, etc.
- `fx-trigger` - Event to trigger request
- `fx-confirm` - Show confirmation dialog before request
- `fx-reset` - Reset form after successful submission
- `fx-boost="false"` - Disable SPA navigation for element/descendants
- `fx-ignore` - Skip processing for element/descendants

**Boost behavior:**

- All internal links (`<a href="/...">`) are fetched via AJAX
- All forms (without `fx-action`) submit via AJAX
- Uses View Transitions API for smooth page swaps
- Disable with `fx-boost="false"` on element or ancestor

CSRF token auto-injected via `fx:config` event in layout.php.

## Build System

Assets are built with Vite and served from `app/public/dist/` with content hashes for cache-busting.

```bash
bun run dev    # Start Vite dev server with HMR
bun run build  # Production build with optimizations
```

### Vite Plugins

- **fontaine**: Generates font fallbacks to reduce CLS
- **vite-plugin-purgecss**: Removes unused CSS
- **vite-plugin-thumbnails.js**: Auto-generates avatar thumbnails (32x32 AVIF)
- **vite-plugin-critical-css.js**: Extracts above-the-fold CSS for inlining

### Asset Loading Strategy

The `Vite` helper (`app/lib/Vite.php`) handles asset injection:

1. **Critical CSS** - Inlined in `<head>` for instant first paint
2. **Full CSS** - Loaded async via preload/swap pattern (non-render-blocking)
3. **JavaScript** - Modulepreload + deferred execution

```php
// In layout.php
<?= \App\Lib\Vite::assets('resources/js/app.js') ?>
```

## Development

### Live Reload

Changes to PHP files reflect immediately (Docker volume mount + OPcache with `validate_timestamps=1`).

### Profiling

```bash
make profile-home   # Profile homepage
make profile-tag    # Profile tag filter
make profile-page   # Profile article page
```

Cachegrind files output to `profiling/` directory.

### Test Users (after `make seed`)

See `database/data/seed.yaml` for test accounts.

## Environment Variables

- `JWT_SECRET`: Secret key for JWT signing (required in production)
- `DB_*`: Database configuration (see above)

## Infrastructure

- **FrankenPHP**: Modern PHP server on Caddy
- **Docker**: Development container on port 8082
- **Caddyfile**: Configures routing, compression (br/zstd/gzip), and caching headers

## Planned Work

### Required / Expected Features

- [ ] Tag input UI component with autocompletion
- [ ] Paginator with page numbers (consider Nette Paginator)

### Performance Improvements

- [ ] Pre-compile Markdown to HTML and store in DB

### Future Enhancements

- [ ] Templating language (Latte?) for faster compilation
- [ ] Require strong/high-entropy passwords
