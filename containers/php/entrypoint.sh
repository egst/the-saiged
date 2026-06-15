#!/bin/sh
# Ensure the uploads volume (mounted at runtime, owned by root) is writable
# by the www-data user that php-fpm workers run as.
chown -R www-data:www-data /var/www/the-saiged/data/uploads 2>/dev/null || true
exec docker-php-entrypoint "$@"
