<?php

declare(strict_types=1);

use Symfony\Component\HttpClient\HttpClient;

include __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
include __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';

sleep(60);

// if no display token is available, quit
$displayToken = null;
if (file_exists('/boot/gazeto-display-token.txt') === false) {
    echo 'No display token is set';
    exit(103);
}
$displayToken = file_get_contents('/boot/gazeto-display-token.txt');

$urls = null;
if (file_exists('/boot/gazeto-urls.json') === false) {
    echo 'No urls file is given';
    exit(104);
}
$urls = json_decode(file_get_contents('/boot/gazeto-urls.json'), true);

$client = HttpClient::create([
    'headers' => ['Authorization' => 'Token ' . $displayToken]
]);

while(true) {
    try {
        $tasksResponse = $client->request('GET', $urls['tasks'])->toArray(false);
        foreach ($tasksResponse['tasks'] as $task) {
            $result = match ($task['task']) {
                'reboot' => reboot(...$task['args']),
                'report' => report(...$task['args']),
                'package-update' => packageUpdate(...$task['args']),
                'gazeto-update' => gazetoUpdate(...$task['args']),
                'refresh-urls' => refreshUrls(...$task['args']),
                'write-screen-config' => writeScreenConfiguration(...$task['args']),
            };
            $client->request('POST', $task['report'], [
                'body' => $result
            ])->toArray();
        }
    } catch (\Exception $e) {
        echo 'Failure ' . get_class($e) . ' / ' . $e->getMessage() . ' / ' . $e->getTraceAsString();
        exit(105);
    }

    sleep(60);
}
