FROM php:7.2-cli
COPY . /var/www/html
WORKDIR /var/www/html

# Git
RUN apt-get update && apt-get install -y git-all

# PHP Unit
RUN apt-get update && apt-get install -y wget
RUN wget https://phar.phpunit.de/phpunit.phar -O phpunit.phar
RUN chmod +x phpunit.phar
RUN mv phpunit.phar /usr/local/bin/phpunit
#RUN phpunit --version

# zip
RUN buildRequirements="zlib1g-dev" \
	&& apt-get update && apt-get install -y ${buildRequirements} \
	&& docker-php-ext-install zip \
	&& apt-get purge -y ${buildRequirements} \
	&& rm -rf /var/lib/apt/lists/*

# soap
RUN buildRequirements="libxml2-dev" \
	&& apt-get update && apt-get install -y ${buildRequirements} \
	&& docker-php-ext-install soap \
	&& apt-get purge -y ${buildRequirements} \
	&& rm -rf /var/lib/apt/lists/*

RUN echo "date.timezone = Europe/Paris" > /usr/local/etc/php/conf.d/date.ini

# composer
RUN curl -sS https://getcomposer.org/installer | php -- --filename=composer -- --install-dir=/usr/local/bin
RUN composer install --no-interaction
RUN chmod 777 tests/phpunit.xml

CMD ["vendor/phpunit/phpunit/phpunit", "-c tests/phpunit.xml"]
