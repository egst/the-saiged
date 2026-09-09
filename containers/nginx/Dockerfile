FROM nginx:1.27-alpine

COPY public                     /var/www/the-saiged/public
COPY src/Sections               /var/www/the-saiged/src/Sections
COPY src/Shell                  /var/www/the-saiged/src/Shell
COPY src/Typography             /var/www/the-saiged/src/Typography
COPY containers/nginx/prod.conf /etc/nginx/conf.d/default.conf
