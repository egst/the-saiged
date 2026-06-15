FROM nginx:1.27-alpine

COPY public                     /var/www/the-saiged/public
COPY src/Sections               /var/www/the-saiged/src/Sections
COPY containers/nginx/prod.conf /etc/nginx/conf.d/default.conf
