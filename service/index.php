<?php

header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');    // cache for 1 day

$_POST = file_get_contents("php://input");
$_POST = json_decode($_POST, true);

if(!isset($_POST['hash']) || $_POST['hash'] !== '0a0276f4849ca0f1785d') {
    return;
}

if(!isset($_POST['domains'])) {
    return;
}

require_once __DIR__ . "/nginx.template.php";

$domains = $_POST['domains'];

foreach ($domains as $domain) {
    $data = getTemplate($domain);
    $path = "/var/sites-data/nginx-conf.d/$domain.conf";
    file_put_contents($path, $data);
}

echo '<pre>';
exec('sudo /usr/sbin/service nginx reload', $content,$ret);

if($ret === 0) {
    print_r('Конфигурация nginx обновлена');
}
echo '</pre>';
//
//foreach ($domains as $domain) {
//
//
//    $data = getHttpsTemplate($domain);
//    $path = "/var/sites-data/nginx-conf.d/$domain.conf";
//    file_put_contents($path, $data);
//
//
//    $command = "certbot certonly --nginx --non-interactive --agree-tos --no-eff-email --email=$domain@gmail.com -d $domain -d www.$domain --rsa-key-size 4096 --config-dir=/var/sites-data/letsencrypt/config  --work-dir=/var/sites-data/letsencrypt/work --logs-dir=/var/sites-data/letsencrypt/logs";
//    $command = "echo \$PATH";
//    $command = "sudo PATH=\"/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin\"  certbot certonly --nginx --non-interactive --agree-tos --no-eff-email --email=$domain@gmail.com -d $domain -d www.$domain --rsa-key-size 4096 --config-dir=/var/sites-data/letsencrypt/config  --work-dir=/var/sites-data/letsencrypt/work --logs-dir=/var/sites-data/letsencrypt/logs";
//    $command = "sudo PATH=\"/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin\"  certbot certonly --nginx --non-interactive --agree-tos --no-eff-email --email=$domain@gmail.com -d $domain -d www.$domain --rsa-key-size 4096 --config-dir=/var/sites-data/letsencrypt/config  --work-dir=/var/sites-data/letsencrypt/work --logs-dir=/var/sites-data/letsencrypt/logs";
//    $command = "bash /var/sites-data/service/certbot.sh";
//    exec($command.' 2>&1',$content,$ret);
//    print_r($command);
//    print_r($content);
//    print_r($ret);
//
//    $data = getHttpsFullTemplate($domain);
//    $path = "/var/sites-data/nginx-conf.d/$domain.conf";
//    file_put_contents($path, $data);
//    die();
//}

?>



