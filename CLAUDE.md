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
realworld-ludicrous/
├── app/                       # DEPLOYABLE APPLICATION
│   ├── components/            # Reusable UI components (18 total)
│   │   ├── article-actions/   # Edit/Delete or Follow/Favorite buttons
│   │   ├── article-list/      # Article preview list with empty state
│   │   ├── article-meta/      # Author avatar, name, date
│   │   ├── article-preview/   # Article card for lists
│   │   ├── banner/            # Homepage hero banner
│   │   ├── comment/           # Comment card with delete
│   │   ├── comment-form/      # Comment input form
│   │   ├── comment-list/      # Comments container
│   │   ├── error-messages/    # Form error display
│   │   ├── favorite-button/   # Favorite/unfavorite button
│   │   ├── feed-toggle/       # Your Feed / Global Feed tabs
│   │   ├── follow-button/     # Follow/unfollow button
│   │   ├── footer/            # Site footer
│   │   ├── navbar/            # Navigation bar
│   │   ├── pagination/        # Previous/Next links
│   │   ├── profile-tabs/      # My Articles / Favorited tabs
│   │   ├── tag-list/          # Tags (sidebar or inline)
│   │   └── user-info/         # Profile header with bio
│   ├── lib/                   # Framework core
│   ├── models/                # Data models (User, Article, Comment)
│   ├── pages/                 # File-based routing (URL = file path)
│   ├── services/              # Business logic (Seeder)
│   ├── templates/             # Latte templates (.latte) with PHP fallback
│   └── public/                # Web root
├── build/                     # Build tooling (dev only)
│   ├── vite.config.js         # Vite build configuration
│   ├── vite-plugin-critical-css.js
│   └── vite-plugin-thumbnails.js
├── infra/                     # Infrastructure (dev only)
│   ├── Dockerfile
│   ├── docker-compose.yml
│   └── Caddyfile
├── resources/                 # Source assets (pre-build)
│   ├── css/
│   └── js/
├── database/                  # Database files
│   ├── migrations/            # Doctrine migrations
│   └── data/seed.yaml
└── docs/                      # Documentation assets
```

The `app/` directory is the deployable application - everything else is development tooling.

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

### Templating with Latte

Templates use the [Latte](https://latte.nette.org/) templating engine with auto-discovered components.

**Installation:** `cd app && composer require latte/latte`

```latte
{* Clean component syntax in .latte files *}
<div class="home-page">
    {Banner()}

    <div class="container">
        {FeedToggle($activeFeed, $activeTag)}
        {ArticleList($articles)}
        {Pagination($page, $limit, $totalItems, '/', [feed => $activeFeed])}
    </div>

    {TagList($tags, 'sidebar')}
</div>
```

Components are auto-registered as functions from `app/components/`:

- `article-preview/` → `{ArticlePreview($article)}`
- `tag-list/` → `{TagList($tags, 'sidebar')}`
- Nested: `wiki/post/` → `{Wiki.Post($article)}` (dot notation)

**Fallback:** If Latte isn't installed, PHP templates (`.php`) are used instead.

### Components

Reusable UI components in `app/components/`. Components can be:

**Full components** (with logic):

- `controller.php` - Logic, data preparation, calls View::component()
- `template.latte` - Latte template with the markup

**Template-only components** (no logic needed):

- Just `template.latte` - Rendered directly, receives props as an array

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
    ];
    View::component(__DIR__ . '/template.php', $props);
}
```

```latte
{* Usage in Latte templates *}
{FavoriteButton($article, $isFavorited)}
{ArticleList($articles)}
{Pagination($page, $limit, $totalItems, '/', [feed => $feed])}
```

```php
// Usage in PHP templates (fallback)
\App\Components\FavoriteButton\render($article, $isFavorited);
\App\Components\ArticleList\render($articles);
```

### Available Components

| Component | Description | Props |
|-----------|-------------|-------|
| `Navbar` | Top navigation | `$currentPage` |
| `Footer` | Site footer | - |
| `Banner` | Homepage hero | - |
| `FeedToggle` | Feed tabs | `$activeFeed`, `$activeTag` |
| `TagList` | Tag pills | `$tags`, `$variant` ('sidebar'\|'inline') |
| `ArticleList` | Article previews | `$articles`, `$emptyMessage` |
| `ArticlePreview` | Single article card | `$article` |
| `ArticleMeta` | Author info | `$article` |
| `ArticleActions` | Edit/Delete/Follow/Favorite | `$article`, `$isFavorited` |
| `Pagination` | Page numbers | `$page`, `$itemsPerPage`, `$totalItems`, `$baseUrl`, `$params` |
| `CommentForm` | Comment input | `$articleSlug` |
| `CommentList` | Comments container | `$comments`, `$articleSlug` |
| `Comment` | Single comment | `$comment`, `$articleSlug` |
| `FavoriteButton` | Heart button | `$article`, `$isFavorited` |
| `FollowButton` | Follow button | `$profile`, `$isFollowing` |
| `UserInfo` | Profile header | `$profile`, `$isFollowing` |
| `ProfileTabs` | My/Favorited tabs | `$username`, `$activeTab` |
| `ErrorMessages` | Form errors | `$errors` |

### Core Libraries

- **Router** (`app/lib/Router.php`): File-based routing with `[param]` syntax. Auto-validates CSRF on non-GET.
- **Database** (`app/lib/Database.php`): Doctrine DBAL singleton with SQLite optimizations.
- **Auth** (`app/lib/Auth.php`): JWT-based authentication via httpOnly cookies.
- **Security** (`app/lib/Security.php`): CSRF protection using HMAC-derived tokens.
- **View** (`app/lib/View.php`): Latte/PHP template rendering with auto-discovered components.
- **ComponentExtension** (`app/lib/ComponentExtension.php`): Latte extension for auto-registering components as functions.
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

### Vite Plugins (in `build/`)

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

## Infrastructure (in `infra/`)

- **FrankenPHP**: Modern PHP server on Caddy
- **Docker**: Development container on port 8082
- **Caddyfile**: Configures routing, compression (br/zstd/gzip), and caching headers

## Planned Work

### Required / Expected Features

- [x] Tag input UI component with autocompletion (Web Component)
- [x] Paginator with page numbers (using Nette Paginator)

### Performance Improvements

- [x] Pre-compile Markdown to HTML and store in DB
- [x] Latte templating for faster compilation (with auto-discovered components)

### Future Enhancements

- [ ] Require strong/high-entropy passwords
