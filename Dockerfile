FROM php:5.6-cli
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

#XDebug
RUN yes | pecl install xdebug-2.5.0 \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_port=9000" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_connect_back=On" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_handler=dbgp" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.profiler_enable=0" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.profiler_output_dir=\"/temp/profiledir\"" >> /usr/local/etc/php/conf.d/xdebug.ini

RUN echo "date.timezone = Europe/Paris" > /usr/local/etc/php/conf.d/date.ini

# composer
RUN curl -sS https://getcomposer.org/installer | php -- --filename=composer -- --install-dir=/usr/local/bin
RUN composer install --no-interaction
RUN chmod 777 tests/phpunit.xml

CMD ["vendor/phpunit/phpunit/phpunit", "-c tests/phpunit.xml"]
