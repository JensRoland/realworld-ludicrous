# RealWorld Example App - Vanilla PHP + boosti.js

A [RealWorld](https://docs.realworld.show/) implementation using vanilla PHP (no framework) and [boosti.js](https://github.com/JensRoland/boosti) for SPA-like navigation.

The purpose of this implementation of the RealWorld spec is to demonstrate the viability of the *traditional monolithic serverside rendered MPA* for building a modern, high-performance web site.

**DEMO: [https://realworld.app.is/](https://realworld.app.is/)**

![Lighthouse 100/100 on Performance](realworld-lighthouse.png)

The demo scores **100/100 on Performance in Lighthouse** (measured for mobile) and is built with a modern & intuitive component-based architecture with vertical slicing and file-based routing; and with HATEOAS principles applied to the frontend.

*Note that the 91/100 Accessibility score is a limitation caused by the original RealWorld design having too-low contrast text. This could be easily fixed, but not without changing the official RealWorld design, thus deviating from the spec.*

## Overview

This project demonstrates a full-stack web application built according to the RealWorld specification, which defines a Medium.com clone with standardized features including:

- User authentication (register, login, logout)
- Article CRUD operations
- Comments on articles
- User profiles
- Article favoriting
- Following other users
- Tag-based article filtering

## Tech Stack

### Backend

- **Vanilla PHP 8.3** - No framework, pure PHP with PSR autoloading
- **File-based routing** - Routes determined by file paths with `[param]` syntax
- **Database-agnostic** - Supports SQLite, MySQL, and PostgreSQL via Doctrine DBAL
- **Composer** - Dependency management and PSR-4 autoloading
- **Parsedown** - Markdown to HTML conversion

### Frontend

- **[boosti.js](https://github.com/JensRoland/boosti)** - Lightweight HTMX alternative for SPA-like navigation
- **[YOLO Mode](https://github.com/JensRoland/boosti?tab=readme-ov-file#yolo-mode-speculative-preloading)** - Just-in-time link prefetching on hover
- **View Transitions API** - Smooth page transitions
- **Vite** - Asset bundling with critical CSS extraction, PurgeCSS, thumbnail generation, and cache-busting

### Infrastructure

- **FrankenPHP** - Modern PHP application server built on Caddy
- **Docker** - Containerized development environment

## Project Structure

```text
realworld-ludicrous/
├── app/
│   ├── components/         # Reusable UI components
│   │   ├── article-meta/   # Author info (avatar, name, date)
│   │   ├── article-preview/# Article card for lists
│   │   ├── comment/        # Comment card
│   │   ├── favorite-button/# Favorite/unfavorite button
│   │   └── follow-button/  # Follow/unfollow button
│   ├── lib/                # Framework core
│   │   ├── Router.php      # File-based router with [param] support
│   │   ├── Database.php    # Doctrine DBAL singleton
│   │   ├── Auth.php        # JWT authentication
│   │   ├── Security.php    # CSRF protection
│   │   ├── View.php        # Template rendering
│   │   ├── Vite.php        # Asset helper with critical CSS
│   │   └── Config.php      # Environment configuration
│   ├── models/             # Data models (User, Article, Comment)
│   ├── services/           # Business logic (Seeder)
│   ├── pages/              # File-based routing (URL = file path)
│   ├── templates/          # PHP view templates
│   ├── public/             # Web root
│   │   ├── dist/           # Vite build output (hashed assets)
│   │   ├── fonts/
│   │   └── img/
│   └── composer.json       # PHP dependencies
├── resources/              # Source assets (pre-build)
│   ├── css/                # Stylesheets (fonts, icons, main)
│   └── js/                 # JavaScript (app.js, boosti, yolomode)
├── database/
│   ├── schema.sql          # SQLite schema
│   ├── schema-mysql.sql    # MySQL schema
│   ├── schema-postgres.sql # PostgreSQL schema
│   └── data/seed.yaml      # Test data
├── vite.config.js          # Vite build configuration
├── docker-compose.yml      # Docker configuration
├── Caddyfile               # Web server configuration
└── Makefile                # Build and run commands
```

## Getting Started

### Prerequisites

- Docker and Docker Compose
- Bun (for asset building)
- Make (optional, but recommended)
- Composer (for local development)

### Setup

1. **Clone the repository**

   ```bash
   git clone <repository-url>
   cd realworld-ludicrous
   ```

2. **Install dependencies and setup database**

   ```bash
   make setup
   bun install
   ```

   This will:
   - Install PHP dependencies via Composer
   - Create the SQLite database
   - Run the database schema
   - Install Node dependencies for Vite

3. **Build assets**

   ```bash
   make build
   ```

4. **Start the application**

   ```bash
   make serve
   ```

   The application will be available at [http://localhost:8082](http://localhost:8082)

### Makefile Commands

- `make setup` - Install PHP dependencies and initialize database
- `make serve` - Start the Docker containers
- `make seed` - Populate the database with test data
- `make clean` - Remove the database (useful for fresh start)
- `make build` - Build CSS/JS assets with Vite
- `bun run dev` - Start Vite dev server with HMR

## Development

The application runs in a Docker container with live reloading. Any changes to PHP files will be reflected immediately without restarting the container.

### Database

The application supports SQLite, MySQL, and PostgreSQL. Configuration is managed through the `.env` file in the project root.

#### SQLite (Default)

SQLite is the default database and requires no additional setup:

```env
DB_DRIVER=pdo_sqlite
DB_PATH=database/database.sqlite
```

To reset the database:

```bash
make clean
make setup
```

#### MySQL

To use MySQL, update your `.env` file:

```env
DB_DRIVER=pdo_mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=realworld
DB_USER=root
DB_PASSWORD=your_password
DB_CHARSET=utf8mb4
```

Then initialize the schema:

```bash
mysql -u root -p realworld < database/schema-mysql.sql
```

#### PostgreSQL

To use PostgreSQL, update your `.env` file:

```env
DB_DRIVER=pdo_pgsql
DB_HOST=localhost
DB_PORT=5432
DB_NAME=realworld
DB_USER=postgres
DB_PASSWORD=your_password
```

Then initialize the schema:

```bash
psql -U postgres -d realworld -f database/schema-postgres.sql
```

### Adding Test Data

You can populate the database with test data using the seeder command:

```bash
make seed
```

Or directly via PHP:

```bash
php database/seed.php
```

The seeder reads data from [database/data/seed.yaml](database/data/seed.yaml), which defines users, articles, comments, follows, and favorites in a human-readable YAML format. You can customize this file or create your own:

```bash
php database/seed.php path/to/custom-seed.yaml
```

## Architecture Highlights

### File-Based Routing

Routes are determined by file paths in `app/pages/`:

| File                                   | Route                            |
| -------------------------------------- | -------------------------------- |
| `pages/index.php`                      | `/`                              |
| `pages/login.php`                      | `/login`                         |
| `pages/article/[slug]/index.php`       | `/article/{slug}`                |
| `pages/profile/[username]/follow.php`  | `/profile/{username}/follow`     |

Parameters in brackets become variables: `[slug].php` means `$slug` is available in the file.

### Components

Reusable UI components live in `app/components/`. Each component has:

- `controller.php` - Logic and data preparation
- `template.php` - Pure HTML presentation

```php
// Usage in templates
\App\Components\FavoriteButton\render($article, $isFavorited);
\App\Components\Comment\render($comment, $articleSlug);
```

### Backend Features

- **Router** - File-based routing in [app/lib/Router.php](app/lib/Router.php)
- **Database** - Doctrine DBAL for database-agnostic queries in [app/lib/Database.php](app/lib/Database.php)
- **Security** - Password hashing and JWT token generation in [app/lib/Security.php](app/lib/Security.php)
- **View** - Template rendering with layout support in [app/lib/View.php](app/lib/View.php)
- **Config** - Environment-based configuration in [app/lib/Config.php](app/lib/Config.php)

### Frontend Features

- **boosti.js** - Lightweight HTMX alternative (~2.9 KB Brotli'd) with `fx-*` attributes for AJAX interactions
- **View Transitions** - Smooth, SPA-like page transitions via the View Transitions API
- **YOLO Mode** - Just-in-time link prefetching on hover for instant navigation
- **Critical CSS** - Above-the-fold styles inlined for fast first paint
- **Async CSS Loading** - Full stylesheet loaded non-render-blocking via preload/swap
- **Progressive Enhancement** - Works without JavaScript for basic functionality

## RealWorld Specification

This implementation follows the [RealWorld specification](https://docs.realworld.show/), which ensures compatibility with other RealWorld implementations and provides a standardized feature set for comparison.

## License

This project is licensed under the terms specified in [LICENSE](LICENSE).

## Code of Conduct

Please see [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) for community guidelines.

## Contributing

Contributions are welcome! This is a demonstration project showing how to build a modern web application with vanilla PHP and HTMX, proving that you don't always need heavy frameworks.
