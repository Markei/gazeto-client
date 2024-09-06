<?php

declare(strict_types=1);

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

include __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$client = HttpClient::create();

echo 'Gazeto init client' . PHP_EOL;

$deviceToken = '';
if (file_exists('/boot/gazeto-device-token.txt') === true) {
    $deviceToken = trim(file_get_contents('/boot/gazeto-device-token.txt'));
    echo 'Device token: ' . $deviceToken . PHP_EOL;
} else {
    echo 'Device token not set' . PHP_EOL;
    exit(100);
}

$authToken = '';
$exchangeToken = '';
if (file_exists('/boot/gazeto-exchange-token.txt') === true && file_exists('/boot/gazeto-auth-token.txt') === true) {
    $authToken = trim(file_get_contents('/boot/gazeto-auth-token.txt'));
    $exchangeToken = trim(file_get_contents('/boot/gazeto-exchange-token.txt'));
    echo 'Auth token already set: ' . $authToken . PHP_EOL;
    echo 'Exchange token already set: ' . $exchangeToken . PHP_EOL;
} else {
    try {
        $inviteResponse = $client->request('POST', 'https://www.markeigazeto.nl/api/invite', [
            'body' => ['deviceToken' => $deviceToken]
        ])->toArray();
        if ($inviteResponse['code'] !== 'INVITE_CREATED') {
            echo 'Invite declined' . PHP_EOL;
            exit(101);
        }
    } catch (HttpExceptionInterface $e) {
        echo 'Could not make contact with Gazeto server, code=' . $e->getResponse()->getStatusCode() . ' body=' . $e->getResponse()->getContent(false) . PHP_EOL;
        exit(102);
    }
    $authToken = $inviteResponse['authToken'];
    $exchangeToken = $inviteResponse['exchangeToken'];
    file_put_contents('/boot/gazeto-auth-token.txt', $authToken);
    file_put_contents('/boot/gazeto-exchange-token.txt', $exchangeToken);
    echo 'Auth token pulled: ' . $authToken . PHP_EOL;
    echo 'Exchange token pulled: ' . $exchangeToken . PHP_EOL;
}

$displayToken = '';
if (file_exists('/boot/gazeto-display-token.txt') === true) {
    $displayToken = trim(file_get_contents('/boot/gazeto-display-token.txt'));
    echo 'Display token: ' . $displayToken . PHP_EOL;
} else {
    while(true) {
        sleep(60);
        echo 'Try to exchange token...' . PHP_EOL;
        $exchangeResponse = $client->request('GET', 'https://www.markeigazeto.nl/api/invite', [
            'headers' => ['Authorization' => 'Token ' . $exchangeToken]
        ])->toArray();

        if ($exchangeResponse['code'] === 'CLAIM_SUCCESS') {
            $displayToken = $exchangeResponse['displayToken'];
            echo 'Display token: ' . $displayToken . PHP_EOL;
            file_put_contents('/boot/gazeto-display-token.txt', $exchangeResponse['displayToken']);
            file_put_contents('/boot/gazeto-urls.json', json_encode($exchangeResponse['urls']));

            file_put_contents('/etc/nginx/sites-available/default', str_replace([
                '__DISPLAYTOKEN__',
                '__SHOWURL__'
            ], [
                $exchangeResponse['displayToken'],
                $exchangeResponse['urls']['show']
            ], file_get_contents('/etc/nginx/default.template')));

            exec('systemctl reboot');
        }
    }
}

exit(0);

