#!/bin/bash

DIR_NAME="sites-data"
BASE_DIR="/var/sites-data"
FILE="${BASE_DIR}/service/isinit"

if [ -f $FILE ]; then
   echo "System already initialized"
else
  chmod -R 777 $BASE_DIR
  apt install nginx -y
  systemctl enable nginx
  apt install openssl php-bcmath php-curl php-json php-mbstring php-mysql php-tokenizer php-xml php-zip php-fpm -y
  service php8.3-fpm start
  apt install php-cli unzip -y


  sudoersFile=$(ls -t /etc/sudoers.d | head -1)
  echo "www-data ALL=(ALL) NOPASSWD: /usr/sbin/service nginx reload" >> "/etc/sudoers.d/${sudoersFile}"

  FILE_HOSTINGER="/etc/nginx/conf.d/hostinger.conf"

  if [ -f $FILE_HOSTINGER ]; then
     echo "hostinger.conf already added"
  else
    sudo  bash -c 'echo "include /var/sites-data/nginx-conf.d/*.conf;" >> /etc/nginx/conf.d/hostinger.conf'
  fi

  sudo apt-get install python3-certbot-nginx

  part1=`cat /var/sites-data/service/server.conf.part1`
  part2=`cat /var/sites-data/service/server.conf.part2`
  serverIp=` hostname -I | grep -Eo '^[0-9.]+'`
  echo "${part1}${serverIp}${part2}" > '/var/sites-data/nginx-conf.d/_server.com.conf'

  nginx -t && systemctl reload nginx
  echo 1 > /var/sites-data/service/isinit
  echo "System success initialized"
fi


