compose_command = docker-compose run -u $(id -u ${USER}):$(id -g ${USER}) --rm php81

build:
	docker-compose build

shell: build
	$(compose_command) bash

destroy:
	docker-compose down -v

composer: build
	$(compose_command) composer install

test: build
	$(compose_command) vendor/bin/phpunit

