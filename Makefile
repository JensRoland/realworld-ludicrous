serve:
	docker-compose up --build

setup:
	cd backend && composer install
	cd backend && composer dump-autoload --optimize --classmap-authoritative
	sqlite3 backend/database.sqlite < backend/schema.sql

seed:
	php backend/seed.php

optimize:
	cd backend && composer dump-autoload --optimize --classmap-authoritative

clean:
	rm backend/database.sqlite
	sqlite3 backend/database.sqlite < backend/schema.sql

profile-home:
	curl "http://localhost:8082/?XDEBUG_PROFILE=1"

profile-tag:
	curl "http://localhost:8082/?tag=startups&XDEBUG_PROFILE=1"

profile-page:
	curl "http://localhost:8082/article/middle-out-compression-the-algorithm-that-changed-everything?XDEBUG_PROFILE=1"
