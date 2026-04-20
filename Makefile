.PHONY: install fixtures lint test

install:
	composer install
	docker compose up -d database
	@until docker compose exec database pg_isready -U hospes > /dev/null 2>&1; do sleep 1; done
	php bin/console doctrine:database:create
	php bin/console doctrine:migrations:migrate
	php bin/console lexik:jwt:generate-keypair
	symfony server:start

fixtures:
	php bin/console doctrine:fixtures:load --no-interaction

lint:
	vendor/bin/phpstan analyse -l 6 src --memory-limit=256M
	vendor/bin/php-cs-fixer fix


test:
	vendor/bin/phpunit --testsuite=unit
	vendor/bin/phpunit --testsuite=integration
	vendor/bin/phpunit --testsuite=functional