FROM ubuntu:focal

ARG DEBIAN_FRONTEND=noninteractive

# PHP
RUN apt-get update -y

RUN apt install -y apache2 vim software-properties-common

RUN add-apt-repository ppa:ondrej/php

RUN apt-get install --no-install-recommends -y \
    php8.1 \
    php8.1-common \
    php8.1-gd \
    php8.1-zip \
    php8.1-curl \
    php8.1-xml \
    php8.1-xmlrpc \
    php8.1-mbstring \
    php8.1-sqlite \
    php8.1-xdebug \
    php8.1-pgsql \
    php8.1-intl \
    php8.1-imagick \
    php8.1-gmp \
    php8.1-apcu \
    php8.1-bcmath \
    php8.1-redis \
    php8.1-soap \
    php8.1-imap \
    php8.1-opcache \
    php8.1-cli \
    php8.1-dev \
    libmagickcore-6.q16-3-extra \
    curl \
    lsof \
    make \
    nodejs \
    npm

RUN echo "xdebug.remote_enable = 1" >> /etc/php/8.1/cli/conf.d/20-xdebug.ini
RUN echo "xdebug.remote_autostart = 1" >> /etc/php/8.1/cli/conf.d/20-xdebug.ini

# Docker
RUN apt-get -y install \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg-agent \
    software-properties-common
RUN curl -fsSL https://download.docker.com/linux/ubuntu/gpg | apt-key add -
RUN add-apt-repository \
   "deb [arch=amd64] https://download.docker.com/linux/ubuntu \
   $(lsb_release -cs) \
   stable"
RUN apt-get update -y
RUN apt-get install -y docker-ce docker-ce-cli containerd.io
RUN ln -s /var/run/docker-host.sock /var/run/docker.sock

WORKDIR /var/www/html
