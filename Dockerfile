FROM mariadb

ENV COMPOSER_ALLOW_SUPERUSER 1
ENV PERFORMANCE_OPTIM false
ENV MYSQL_ROOT_PASSWORD root_db_password

RUN apt-get update
RUN apt-get install -y --no-install-recommends ca-certificates software-properties-common

RUN add-apt-repository ppa:ondrej/php -y

RUN apt-get -qq update && DEBIAN_FRONTEND=noninteractive apt-get -y install \
    screen \
    gnupg \
    ca-certificates \
    nginx \
    wget \
    unzip \
    php8.0 \
    php8.0-cli \
    php8.0-intl \
    php8.0-fpm \
    php8.0-xml \
    php8.0-mbstring \
    php8.0-curl \
    php8.0-mysql &&\
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* &&\
    php -r "readfile('https://getcomposer.org/installer');" | php -- \
             --install-dir=/usr/local/bin \
             --filename=composer &&\
    echo "daemon off;" >> /etc/nginx/nginx.conf &&\
    mkdir -p /run/php

VOLUME ["/etc/mysql", "/var/lib/mysql"]

ADD . /var/www
ADD ./build/nginx/default /etc/nginx/sites-enabled/default

EXPOSE 80

RUN chmod +x /var/www/build/entrypoint.sh
RUN chown -R root:www-data /var/www/var/cache

ENTRYPOINT ["/var/www/build/entrypoint.sh"]
