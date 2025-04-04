<?php

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/nginx.template.php";

use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;
use Afosto\Acme\Client;

header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');    // cache for 1 day

function getCertificate($domain)
{
    $adapter = new Local('/var/www/html/' . $domain);
    $filesystem = new Filesystem($adapter);

    $client = new Client([
        'username' => 'admin@' . $domain,
        'fs' => $filesystem,
        'mode' => Client::MODE_LIVE,
        'source_ip' => $_SERVER['SERVER_ADDR']
    ]);

    $order = $client->createOrder([$domain, 'www.' . $domain]);
    $authorizations = $client->authorize($order);
    foreach ($authorizations as $authorization) {
        $file = $authorization->getFile();

        if (!is_dir('/var/www/html/' . $domain . '/')) {
            mkdir('/var/www/html/' . $domain . '/');
        }

        if (!is_dir('/var/www/html/' . $domain . '/.well-known')) {
            mkdir('/var/www/html/' . $domain . '/.well-known');
        }
        if (!is_dir('/var/www/html/' . $domain . '/.well-known/acme-challenge')) {
            mkdir('/var/www/html/' . $domain . '/.well-known/acme-challenge');
        }

        file_put_contents('/var/www/html/' . $domain . '/.well-known/acme-challenge/' . $file->getFilename(), $file->getContents());
    }

    if (!$client->selfTest($authorization, Client::VALIDATION_HTTP)) {
        throw new \Exception('Could not verify ownership via HTTP');
    }


    foreach ($authorizations as $authorization) {
        $client->validate($authorization->getHttpChallenge(), 15);
    }

    if ($client->isReady($order)) {

        $certificate = $client->getCertificate($order);

        if (!is_dir('/var/sites-data/cert/' . $domain)) {
            mkdir('/var/sites-data/cert/' . $domain);
        }

        file_put_contents('/var/sites-data/cert/' . $domain . '/certificate.pem', $certificate->getCertificate());
        file_put_contents('/var/sites-data/cert/' . $domain . '/private.pem', $certificate->getPrivateKey());
    }
}

function reloadNginx()
{
    exec('sudo /usr/sbin/service nginx reload', $content, $ret);

    if ($ret === 0) {
        print_r("Конфигурация nginx обновлена\r\n");
    }
}

$_POST = file_get_contents("php://input");
$_POST = json_decode($_POST, true);

if (!isset($_POST['hash']) || $_POST['hash'] !== '0a0276f4849ca0f1785d') {
    return;
}

if (!isset($_POST['domains'])) {
    return;
}

try {

    $domains = $_POST['domains'];

    foreach ($domains as $domain) {
        $data = getTemplate($domain);
        $path = "/var/sites-data/nginx-conf.d/$domain.conf";
        file_put_contents($path, $data);

        reloadNginx();

        getCertificate($domain);
        $data = getHttpsFullTemplate($domain);
        $path = "/var/sites-data/nginx-conf.d/$domain.conf";
        file_put_contents($path, $data);
    }

    reloadNginx();

} catch (\Exception $e) {
    print_r($e->getMessage());
}
