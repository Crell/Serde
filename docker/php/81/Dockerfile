FROM php:8.1.0beta2-cli
WORKDIR /usr/src/myapp
CMD [ "vendor/bin/wait-for-it.sh", "db:3306", "--", "vendor/bin/phpunit" ]
#CMD [ "ls", "-alF" ]
#CMD ["pwd"]

RUN docker-php-ext-install pdo_mysql
