# RealWorld Example App - Vanilla PHP + HTMX

A [RealWorld](https://docs.realworld.show/) implementation using vanilla PHP (no framework) and HTMX for dynamic interactions.

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
- **Database-agnostic** - Supports SQLite, MySQL, and PostgreSQL via Doctrine DBAL
- **Composer** - Dependency management and PSR-4 autoloading
- **Parsedown** - Markdown to HTML conversion

### Frontend

- **HTMX** - Dynamic HTML interactions without complex JavaScript
- **Vanilla CSS** - Following RealWorld styling specifications
- **Minimal JavaScript** - Only where absolutely necessary

### Infrastructure

- **FrankenPHP** - Modern PHP application server built on Caddy
- **Docker** - Containerized development environment

## Project Structure

```text
realworld-ludicrous/
├── backend/
│   ├── src/
│   │   ├── Controllers/    # Request handlers
│   │   ├── Models/         # Data models
│   │   ├── Core/           # Router, Database, Security, View
│   │   └── Services/       # Seeder and other services
│   ├── templates/          # PHP view templates
│   ├── schema.sql          # Database schema
│   └── composer.json       # PHP dependencies
├── frontend/
│   └── public/
│       ├── css/            # Stylesheets
│       ├── fonts/          # Web fonts
│       ├── js/             # HTMX and minimal JS
│       └── index.php       # Application entry point
├── docker-compose.yml      # Docker configuration
├── Caddyfile               # Web server configuration
└── Makefile                # Build and run commands
```

## Getting Started

### Prerequisites

- Docker and Docker Compose
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
   ```

   This will:
   - Install PHP dependencies via Composer
   - Create the SQLite database
   - Run the database schema

3. **Start the application**

   ```bash
   make serve
   ```

   The application will be available at [http://localhost:8082](http://localhost:8082)

### Makefile Commands

- `make setup` - Install dependencies and initialize database
- `make serve` - Build and start the Docker containers
- `make seed` - Populate the database with test data
- `make clean` - Remove the database (useful for fresh start)

## Development

The application runs in a Docker container with live reloading. Any changes to PHP files will be reflected immediately without restarting the container.

### Database

The application supports SQLite, MySQL, and PostgreSQL. Configuration is managed through the `.env` file in the project root.

#### SQLite (Default)

SQLite is the default database and requires no additional setup:

```env
DB_DRIVER=pdo_sqlite
DB_PATH=backend/database.sqlite
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
mysql -u root -p realworld < backend/schema-mysql.sql
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
psql -U postgres -d realworld -f backend/schema-postgres.sql
```

### Adding Test Data

You can populate the database with test data using the seeder command:

```bash
make seed
```

Or directly via PHP:

```bash
php backend/seed.php
```

The seeder reads data from [backend/data/seed.yaml](backend/data/seed.yaml), which defines users, articles, comments, follows, and favorites in a human-readable YAML format. You can customize this file or create your own:

```bash
php backend/seed.php path/to/custom-seed.yaml
```

The seeder is idempotent and can be run multiple times safely (implementation in [backend/src/Services/Seeder.php](backend/src/Services/Seeder.php)).

## Architecture Highlights

### Backend

- **Router** - Simple but effective routing system in [backend/src/Core/Router.php](backend/src/Core/Router.php)
- **Database** - Doctrine DBAL for database-agnostic queries in [backend/src/Core/Database.php](backend/src/Core/Database.php)
- **Security** - Password hashing and JWT token generation in [backend/src/Core/Security.php](backend/src/Core/Security.php)
- **View** - Template rendering with layout support in [backend/src/Core/View.php](backend/src/Core/View.php)
- **Config** - Environment-based configuration in [backend/src/Core/Config.php](backend/src/Core/Config.php)

### Frontend

- **HTMX-driven** - Most interactions use HTMX attributes for seamless updates
- **Progressive Enhancement** - Works without JavaScript for basic functionality
- **Minimal Dependencies** - Only HTMX, no heavy frontend frameworks

## RealWorld Specification

This implementation follows the [RealWorld specification](https://docs.realworld.show/), which ensures compatibility with other RealWorld implementations and provides a standardized feature set for comparison.

## License

This project is licensed under the terms specified in [LICENSE](LICENSE).

## Code of Conduct

Please see [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) for community guidelines.

## Contributing

Contributions are welcome! This is a demonstration project showing how to build a modern web application with vanilla PHP and HTMX, proving that you don't always need heavy frameworks.
