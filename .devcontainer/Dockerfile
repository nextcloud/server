FROM ubuntu:jammy

ARG DEBIAN_FRONTEND=noninteractive

# PHP
RUN apt-get update -y && \
    apt install -y apache2 vim software-properties-common sudo nano gnupg2

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
	unzip

# Composer
RUN curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php && \
    curl -sS https://composer.github.io/installer.sig -o /tmp/composer-setup.sig && \
    php -r "if (hash_file('sha384', '/tmp/composer-setup.php') !== trim(file_get_contents('/tmp/composer-setup.sig'))) { echo 'Composer installation failed, invalid hash'; exit(1); }" && \
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
    rm /tmp/composer-setup.php /tmp/composer-setup.sig

RUN echo "xdebug.remote_enable = 1" >> /etc/php/8.1/cli/conf.d/20-xdebug.ini && \
    echo "xdebug.remote_autostart = 1" >> /etc/php/8.1/cli/conf.d/20-xdebug.ini && \
	echo "apc.enable_cli=1" >> /etc/php/8.1/cli/conf.d/20-apcu.ini

# Autostart XDebug for apache
RUN { \
	echo "xdebug.mode=debug"; \
	echo "xdebug.start_with_request=yes"; \
} >> /etc/php/8.1/apache2/conf.d/20-xdebug.ini

# Docker
RUN apt-get -y install \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg-agent \
    software-properties-common && \
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | apt-key add - && \
    add-apt-repository \
   "deb [arch=$(dpkg --print-architecture)] https://download.docker.com/linux/ubuntu \
   $(lsb_release -cs) \
   stable" && \
    apt-get update -y && \
    apt-get install -y docker-ce docker-ce-cli containerd.io && \
    ln -s /var/run/docker-host.sock /var/run/docker.sock

# Dedicated DevContainer user runs Apache
ENV APACHE_RUN_USER=devcontainer
ENV APACHE_RUN_GROUP=devcontainer
RUN useradd -ms /bin/bash ${APACHE_RUN_USER} && \
	adduser ${APACHE_RUN_USER} sudo && \
	echo '%sudo ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers && \
	sed -ri "s/^export APACHE_RUN_USER=.*$/export APACHE_RUN_USER=${APACHE_RUN_USER}/" "/etc/apache2/envvars" && \
    sed -ri "s/^export APACHE_RUN_GROUP=.*$/export APACHE_RUN_GROUP=${APACHE_RUN_GROUP}/" "/etc/apache2/envvars"

USER devcontainer

# NVM
RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
RUN bash --login -i -c 'source /home/devcontainer/.bashrc && nvm install 16'

WORKDIR /var/www/html
