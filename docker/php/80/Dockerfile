FROM php:8.0.10-cli
WORKDIR /usr/src/myapp
CMD [ "vendor/bin/phpunit" ]
#CMD [ "vendor/bin/wait-for-it.sh", "db:3306", "--", "vendor/bin/phpunit", "--filter", "can_create_tables_from_classes@Crell\Rekodi\Records\AllFieldTypes" ]
#CMD [ "ls", "-alF" ]
#CMD ["pwd"]

#RUN pecl install xdebug
RUN pecl install xdebug-3.1.0beta2
