install:
	bun install
	cd app && composer install

build:
	bun run build

serve:
	docker-compose -f infra/docker-compose.yml up --build

setup: install
	cd app && composer dump-autoload --optimize --classmap-authoritative
	php app/migrations.php migrations:migrate --no-interaction

seed:
	php database/seed.php

optimize:
	cd app && composer dump-autoload --optimize --classmap-authoritative

clean:
	rm -f database/database.sqlite
	php app/migrations.php migrations:migrate --no-interaction

migrate:
	php app/migrations.php migrations:migrate --no-interaction

migrate-status:
	php app/migrations.php migrations:status

migrate-generate:
	php app/migrations.php migrations:generate

profile-home:
	curl "http://localhost:8082/?XDEBUG_PROFILE=1"

profile-tag:
	curl "http://localhost:8082/?tag=startups&XDEBUG_PROFILE=1"

profile-page:
	curl "http://localhost:8082/article/middle-out-compression-the-algorithm-that-changed-everything?XDEBUG_PROFILE=1"
