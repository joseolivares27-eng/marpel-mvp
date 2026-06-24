build:
	docker compose build

up:
	docker compose up -d

down:
	docker compose down

migrate:
	docker compose exec app php artisan migrate

seed:
	docker compose exec app php artisan db:seed

logs:
	docker compose logs -f app
