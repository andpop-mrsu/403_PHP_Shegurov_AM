.PHONY: install serve

install:
	@if [ ! -f .env ]; then cp .env.example .env; fi
	composer install
	mkdir -p database
	touch database/database.sqlite
	php artisan key:generate --force
	php artisan optimize:clear
	php artisan migrate --force

serve:
	php artisan serve
