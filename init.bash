#!/bin/bash

DIR_NAME="sites-data"
BASE_DIR="/var/sites-data"
SITES_DIR="/var/www/html"
FILE="${BASE_DIR}/service/isinit"

if [ -f $FILE ]; then
   echo "System already initialized"
else
  chmod -R 777 $BASE_DIR
  chmod -R 777 $SITES_DIR

  # Установка php, nginx и модулей
  apt install nginx -y
  systemctl enable nginx
  apt install openssl php-bcmath php-curl php-json php-mbstring php-mysql php-tokenizer php-xml php-zip php-fpm -y
  service php8.3-fpm start
  apt install php-cli unzip -y

  # Добавление доступа к nginx reload из php
  sudoersFile=$(ls -t /etc/sudoers.d | head -1)
  echo "www-data ALL=(ALL) NOPASSWD: /usr/sbin/service nginx reload" >> "/etc/sudoers.d/${sudoersFile}"

  # Добавление папки конфигов сайтов
  FILE_HOSTINGER="/etc/nginx/conf.d/hostinger.conf"

  if [ -f $FILE_HOSTINGER ]; then
     echo "hostinger.conf already added"
  else
    sudo  bash -c 'echo "include /var/sites-data/nginx-conf.d/*.conf;" >> /etc/nginx/conf.d/hostinger.conf'
  fi

  # Добавление базового конфига сервера
  part1=`cat /var/sites-data/service/server.conf.part1`
  part2=`cat /var/sites-data/service/server.conf.part2`
  serverIp=` hostname -I | grep -Eo '^[0-9.]+'`
  echo "${part1}${serverIp}${part2}" > '/var/sites-data/nginx-conf.d/_server.com.conf'

  # Применение новой конфигурации
  nginx -t && systemctl reload nginx

  # Установка composer
#  EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
#  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
#  ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"
#
#  if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]
#  then
#      >&2 echo 'ERROR: Invalid installer checksum'
#      rm composer-setup.php
#      exit 1
#  fi
#
#  php composer-setup.php --quiet
#  RESULT=$?
#  rm composer-setup.php

  # Отмечаем, что сервер уже инициализирован
  echo 1 > /var/sites-data/service/isinit
  echo "System success initialized"
fi

