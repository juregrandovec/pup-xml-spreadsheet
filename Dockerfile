FROM php:8.1-fpm

# Arguments defined in docker-compose.yml
ARG user
ARG uid

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u $uid -d /home/$user $user || echo "User root already exists."
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user
RUN apt-get -y update

# Set working directory
WORKDIR /var/www

USER $user