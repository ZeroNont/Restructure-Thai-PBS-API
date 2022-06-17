FROM php:8.1.1-apache

RUN apt-get update && \
    apt-get install -y libpq-dev libcurl4-openssl-dev pkg-config libssl-dev libldap2-dev && \
    pecl install mongodb && \
    docker-php-ext-enable mongodb && \
    docker-php-ext-install pdo pdo_mysql && \
    docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ && \
    docker-php-ext-install ldap && \
    a2enmod rewrite && \
    apt-get install -y git zip unzip && \
    php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer && \
    apt-get -y autoremove && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*\
    sudo chmod -R 777 storage && sudo chmod -R 777 bootstrap/cache

COPY config/upload.ini /usr/local/etc/php/conf.d/upload.ini

COPY config/vhost.conf /etc/apache2/sites-available/000-default.conf