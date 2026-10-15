FROM python:3.12.14-slim-bookworm AS python_runtime

FROM php:8.4.22-apache-bookworm

COPY --from=python_runtime /usr/local /usr/local

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl libonig-dev libpq-dev libzip-dev \
    && docker-php-ext-install -j"$(nproc)" mbstring pdo_pgsql zip \
    && a2enmod headers rewrite \
    && sed -ri 's!Listen 80!Listen 8080!' /etc/apache2/ports.conf \
    && sed -ri 's!^export APACHE_RUN_USER=.*!export APACHE_RUN_USER=www-data!; s!^export APACHE_RUN_GROUP=.*!export APACHE_RUN_GROUP=www-data!; s!^export APACHE_PID_FILE=.*!export APACHE_PID_FILE=/tmp/apache2.pid!; s!^export APACHE_RUN_DIR=.*!export APACHE_RUN_DIR=/tmp!; s!^export APACHE_LOCK_DIR=.*!export APACHE_LOCK_DIR=/tmp!; s!^export APACHE_LOG_DIR=.*!export APACHE_LOG_DIR=/tmp!' /etc/apache2/envvars \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt /tmp/requirements.txt
RUN python3.12 -m venv /opt/servimeters/venv \
    && /opt/servimeters/venv/bin/pip install --no-cache-dir --requirement /tmp/requirements.txt \
    && rm /tmp/requirements.txt

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php-servimeters.ini /usr/local/etc/php/conf.d/zz-servimeters.ini
COPY . /var/www/html

RUN mkdir -p /var/www/html/storage/uploads /var/www/html/storage/sessions /var/www/html/tmp \
    && chown -R www-data:www-data /var/www/html/storage/uploads /var/www/html/storage/sessions /var/www/html/tmp

ENV PYTHONDONTWRITEBYTECODE=1 \
    SERVIMETERS_PYTHON_PATH=/opt/servimeters/venv/bin/python \
    SERVIMETERS_SESSION_PATH=/var/www/html/storage/sessions \
    SERVIMETERS_TEMP_PATH=/var/www/html/tmp

USER www-data
EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
  CMD curl --fail --silent http://127.0.0.1:8080/api/health.php >/dev/null || exit 1

CMD ["apache2-foreground"]
