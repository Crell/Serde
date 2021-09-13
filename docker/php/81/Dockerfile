FROM php:8.1.0RC1-cli
WORKDIR /usr/src/myapp
CMD [ "vendor/bin/phpunit" ]
#CMD [ "vendor/bin/wait-for-it.sh", "db:3306", "--", "vendor/bin/phpunit" ]
#CMD [ "ls", "-alF" ]
#CMD ["pwd"]

RUN pecl install xdebug-3.1.0beta2
