<?php

function getTemplate($host_name)
{
    return
"
server {
    listen 80;
    listen [::]:80;

    root /var/www/$host_name;
    index index.html index.htm index.nginx-debian.html;

    server_name $host_name www.$host_name;

    location ~ /.well-known/acme-challenge {
        allow all;
        root /var/www/html/$host_name;
        try_files \$uri index.html;
    }
        
    location / {
        proxy_set_header X-Domain-Name $host_name;
        proxy_set_header X-Client-IP \$remote_addr;
        proxy_pass http://116.202.171.211/sites;
        proxy_redirect off;
    }
}
";
}

function getHttpsFullTemplate($host_name)
{
    return "
    
server {
    listen 80;
    listen [::]:80;

    server_name $host_name;

    location ~ /.well-known/acme-challenge {
        allow all;
        root /var/www/html/$host_name;
        try_files \$uri index.html;
    }
        
    location / {
        return 301 https://\$host\$request_uri;
    }
}

server {
        listen 443 ssl http2;
        listen [::]:443 ssl http2;
        server_name $host_name;

        index index.php index.html index.htm;

        root /var/www/$host_name/html;

        server_tokens off;

        ssl_certificate /var/sites-data/cert/$host_name/certificate.pem;
        ssl_certificate_key /var/sites-data/cert/$host_name/private.pem;

        add_header X-Frame-Options \"SAMEORIGIN\" always;
        add_header X-XSS-Protection \"1; mode=block\" always;
        add_header X-Content-Type-Options \"nosniff\" always;
        add_header Referrer-Policy \"no-referrer-when-downgrade\" always;
        add_header Content-Security-Policy \"default-src * data: 'unsafe-eval' 'unsafe-inline'\" always;

        location / {
            proxy_set_header X-Domain-Name $host_name;
            proxy_set_header X-Client-IP \$remote_addr;
            proxy_pass http://116.202.171.211/sites;
            proxy_redirect off;
        }

        location ~ /\.ht {
                deny all;
        }

        location = /favicon.ico {
                log_not_found off;
        }
        location = /robots.txt {
                log_not_found off;
        }
}
";
}

