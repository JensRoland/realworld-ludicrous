serve:
	docker-compose up --build

setup:
	cd app && composer install
	cd app && composer dump-autoload --optimize --classmap-authoritative
	sqlite3 database/database.sqlite < database/schema.sql

seed:
	php database/seed.php

optimize:
	cd app && composer dump-autoload --optimize --classmap-authoritative

clean:
	rm database/database.sqlite
	sqlite3 database/database.sqlite < database/schema.sql

profile-home:
	curl "http://localhost:8082/?XDEBUG_PROFILE=1"

profile-tag:
	curl "http://localhost:8082/?tag=startups&XDEBUG_PROFILE=1"

profile-page:
	curl "http://localhost:8082/article/middle-out-compression-the-algorithm-that-changed-everything?XDEBUG_PROFILE=1"
