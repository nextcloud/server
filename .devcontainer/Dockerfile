FROM ubuntu:noble

ARG DEBIAN_FRONTEND=noninteractive

# PHP
RUN apt-get update -y && \
    apt install -y apache2 vim software-properties-common sudo nano gnupg2 wget curl git \
    lsb-release ca-certificates apt-transport-https && \
    add-apt-repository ppa:ondrej/php -y && \
    apt-get update -y

RUN apt-get install --no-install-recommends -y \
    php8.4 \
    php8.4-common \
    php8.4-gd \
    php8.4-zip \
    php8.4-curl \
    php8.4-xml \
    php8.4-xmlrpc \
    php8.4-mbstring \
    php8.4-sqlite \
    php8.4-xdebug \
    php8.4-pgsql \
    php8.4-intl \
    php8.4-imagick \
    php8.4-gmp \
    php8.4-apcu \
    php8.4-bcmath \
    php8.4-redis \
    php8.4-soap \
    php8.4-imap \
    php8.4-opcache \
    php8.4-cli \
    php8.4-dev \
    libmagickcore-6.q16-7-extra \
    lsof \
    make \
	unzip

# Composer
RUN curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php && \
    curl -sS https://composer.github.io/installer.sig -o /tmp/composer-setup.sig && \
    php -r "if (hash_file('sha384', '/tmp/composer-setup.php') !== trim(file_get_contents('/tmp/composer-setup.sig'))) { echo 'Composer installation failed, invalid hash'; exit(1); }" && \
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
    rm /tmp/composer-setup.php /tmp/composer-setup.sig

RUN echo "xdebug.remote_enable = 1" >> /etc/php/8.4/cli/conf.d/20-xdebug.ini && \
    echo "xdebug.remote_autostart = 1" >> /etc/php/8.4/cli/conf.d/20-xdebug.ini && \
	echo "apc.enable_cli=1" >> /etc/php/8.4/cli/conf.d/20-apcu.ini

# Autostart XDebug for apache
RUN { \
	echo "xdebug.mode=debug"; \
	echo "xdebug.start_with_request=yes"; \
} >> /etc/php/8.4/apache2/conf.d/20-xdebug.ini

# Increase PHP memory limit to 512mb
RUN sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php/8.4/apache2/php.ini

# Docker CLI only (for controlling host Docker via socket)
RUN install -m 0755 -d /etc/apt/keyrings && \
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc && \
    chmod a+r /etc/apt/keyrings/docker.asc && \
    echo \
        "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu \
        $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}") stable" | \
        tee /etc/apt/sources.list.d/docker.list > /dev/null && \
    apt-get update -y && \
    apt-get install -y docker-ce-cli && \
    ln -s /var/run/docker-host.sock /var/run/docker.sock

# Dedicated DevContainer user runs Apache
ENV APACHE_RUN_USER=devcontainer
ENV APACHE_RUN_GROUP=devcontainer
# Delete any existing user/group with UID/GID 1000 first
RUN (getent passwd 1000 && userdel -r $(getent passwd 1000 | cut -d: -f1)) || true && \
    (getent group 1000 && groupdel $(getent group 1000 | cut -d: -f1)) || true && \
    groupadd -g 1000 ${APACHE_RUN_GROUP} && \
    useradd -u 1000 -g 1000 -ms /bin/bash ${APACHE_RUN_USER} && \
	adduser ${APACHE_RUN_USER} sudo && \
	echo '%sudo ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers && \
	sed -ri "s/^export APACHE_RUN_USER=.*$/export APACHE_RUN_USER=${APACHE_RUN_USER}/" "/etc/apache2/envvars" && \
    sed -ri "s/^export APACHE_RUN_GROUP=.*$/export APACHE_RUN_GROUP=${APACHE_RUN_GROUP}/" "/etc/apache2/envvars"

USER devcontainer

# NVM
RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
RUN bash --login -i -c 'source /home/devcontainer/.bashrc && nvm install 22'

WORKDIR /var/www/html
