FROM php:5.6-cli
COPY . /usr/src/myapp
WORKDIR /usr/src/myapp

# PHP Unit
RUN apt-get update && apt-get install -y wget \
    && wget https://phar.phpunit.de/phpunit.phar \
    && chmod +x phpunit.phar \
    && mv phpunit.phar /usr/local/bin/phpunit \
    && phpunit --version

# soap
RUN buildRequirements="libxml2-dev" \
	&& apt-get update && apt-get install -y ${buildRequirements} \
	&& docker-php-ext-install soap \
	&& apt-get purge -y ${buildRequirements} \
	&& rm -rf /var/lib/apt/lists/*

RUN echo "date.timezone = Europe/Paris" > /usr/local/etc/php/conf.d/date.ini

# composer
RUN curl -sS https://getcomposer.org/installer | php

ENTRYPOINT /bin/bash