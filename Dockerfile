FROM php:5.5-cli
COPY . /usr/src/myapp
WORKDIR /usr/src/myapp

# soap
RUN buildRequirements="libxml2-dev" \
	&& apt-get update && apt-get install -y ${buildRequirements} \
	&& docker-php-ext-install soap \
	&& apt-get purge -y ${buildRequirements} \
	&& rm -rf /var/lib/apt/lists/*

# composer
RUN curl -sS https://getcomposer.org/installer | php

ENTRYPOINT /bin/bash