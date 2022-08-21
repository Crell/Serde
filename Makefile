# See https://tech.davis-hansson.com/p/make/ for how all of these customizations work.

SHELL := bash
.ONESHELL:
.SHELLFLAGS := -eu -o pipefail -c
.DELETE_ON_ERROR:
MAKEFLAGS += --warn-undefined-variables
MAKEFLAGS += --no-builtin-rules

ifeq ($(origin .RECIPEPREFIX), undefined)
  $(error This Make does not support .RECIPEPREFIX. Please use GNU Make 4.0 or later)
endif
.RECIPEPREFIX = >

compose_command = docker-compose run -u $(id -u ${USER}):$(id -g ${USER}) --rm php81

build: tmp/.docker-built

tmp/.docker-built: docker-compose.yml docker/php/81/Dockerfile
> mkdir -p $(@D)	# Makes the tmp directory
> docker-compose build
> touch $@			# Touches the file that is this target.

shell: build
> $(compose_command) bash
.PHONY: shell

destroy:
> docker-compose down -v
> rm -rf tmp
.PHONY: destroy

composer: build
> $(compose_command) composer install
.PHONY: composer

test: build
> $(compose_command) vendor/bin/phpunit
.PHONY: test

phpstan: build
> $(compose_command) vendor/bin/phpstan
.PHONY: phpstan

profile: build
> $(compose_command) php profile.php
.PHONY: profile

blackfire:
> $(compose_command) blackfire run php profile.php
.PHONY: blackfire
