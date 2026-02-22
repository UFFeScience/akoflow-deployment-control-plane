APP=app

build:
	docker compose build --no-cache

up:
	docker compose up -d
	docker compose exec $(APP) composer install
	docker compose exec $(APP) php artisan key:generate || true

down:
	docker compose down

stop:
	docker compose stop

bash:
	docker compose exec $(APP) bash

bash-root:
	docker compose exec --user root $(APP) bash

migrate:
	docker compose exec $(APP) php artisan migrate

fresh:
	docker compose exec $(APP) php artisan migrate:fresh --seed

logs:
	docker compose logs -f