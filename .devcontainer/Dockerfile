FROM ubuntu:noble

ARG DEBIAN_FRONTEND=noninteractive

# PHP
RUN apt-get update -y && \
    apt install -y apache2 vim software-properties-common sudo nano gnupg2

RUN apt-get install --no-install-recommends -y \
    php8.3 \
    php8.3-common \
    php8.3-gd \
    php8.3-zip \
    php8.3-curl \
    php8.3-xml \
    php8.3-xmlrpc \
    php8.3-mbstring \
    php8.3-sqlite \
    php8.3-xdebug \
    php8.3-pgsql \
    php8.3-intl \
    php8.3-imagick \
    php8.3-gmp \
    php8.3-apcu \
    php8.3-bcmath \
    php8.3-redis \
    php8.3-soap \
    php8.3-imap \
    php8.3-opcache \
    php8.3-cli \
    php8.3-dev \
    libmagickcore-6.q16-7-extra \
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

RUN echo "xdebug.remote_enable = 1" >> /etc/php/8.3/cli/conf.d/20-xdebug.ini && \
    echo "xdebug.remote_autostart = 1" >> /etc/php/8.3/cli/conf.d/20-xdebug.ini && \
	echo "apc.enable_cli=1" >> /etc/php/8.3/cli/conf.d/20-apcu.ini

# Autostart XDebug for apache
RUN { \
	echo "xdebug.mode=debug"; \
	echo "xdebug.start_with_request=yes"; \
} >> /etc/php/8.3/apache2/conf.d/20-xdebug.ini

# Increase PHP memory limit to 512mb
RUN sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php/8.3/apache2/php.ini

# Docker
RUN apt-get -y install \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg-agent \
    software-properties-common && \
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | apt-key add - && \
    add-apt-repository \
   "deb [arch=amd64] https://download.docker.com/linux/ubuntu \
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
