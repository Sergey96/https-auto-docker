#!/bin/bash

DIR_NAME="sites-data"
BASE_DIR="/var/${DIR_NAME}"
FILE="${BASE_DIR}/service/isinit"

if [ -f $FILE ]; then
   echo "System already initialized"
else
  chmod 777 $BASE_DIR
  apt install nginx -y
  systemctl enable nginx
  apt install openssl php-bcmath php-curl php-json php-mbstring php-mysql php-tokenizer php-xml php-zip php-fpm -y
  service php8.3-fpm start
  apt install php-cli unzip -y

  FILE_SUDOERS="/etc/sudoers.d/nginx.reload"

  if [ -f $FILE_SUDOERS ]; then
     echo "sudoers.d already added"
  else
    sudo bash -c 'echo "www-data ALL=(ALL) NOPASSWD: /usr/sbin/service nginx reload" >> /etc/sudoers.d/nginx.reload'
  fi

  FILE_HOSTINGER="/etc/nginx/conf.d/hostinger.conf"

  if [ -f $FILE_HOSTINGER ]; then
     echo "hostinger.conf already added"
  else
    sudo  bash -c 'echo "include /var/${DIR_NAME}/nginx-conf.d/*.conf;" >> /etc/nginx/conf.d/hostinger.conf'
  fi

  nginx -t && systemctl reload nginx

  sudo apt-get install python3-certbot-nginx

  part1=`cat /var/${DIR_NAME}/service/server.conf.part1`
  part2=`cat /var/${DIR_NAME}/service/server.conf.part2`
  serverIp=` hostname -I | grep -Eo '^[0-9.]+'`
  echo "${part1}${serverIp}${part2}" > '/var/ ${DIR_NAME}/nginx-conf.d/_server.com.conf'

  echo 1 > /var/${DIR_NAME}/service/isinit
  echo "System success initialized"
fi


