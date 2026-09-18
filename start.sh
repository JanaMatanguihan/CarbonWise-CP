#!/bin/sh
sed -i "s/8080/${PORT:-8080}/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf
exec apache2-foreground