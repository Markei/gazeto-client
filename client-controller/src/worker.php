<?php

declare(strict_types=1);

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\HttpClientInterface;

include __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

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
                'gazeto-update' => gazetoUpdate(...$task['args'])
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

function reboot(): string {
    $process = new Process(['systemctl', 'reboot']);
    $process->run();
    return $process->getOutput();
}

function report(): string {
    global $client, $urls;
    assert($client instanceof HttpClientInterface);

    $commands = [
        'linux_distribution_description' => ['cmd' => ['lsb_release', '-d'], 'wd' => '/'],
        'linux_distribution_version' => ['cmd' => ['lsb_release', '-r'], 'wd' => '/'],
        'linux_distribution_codename' => ['cmd' => ['lsb_release', '-c'], 'wd' => '/'],
        'debian_version' => ['cmd' => ['cat', '/etc/debian_version'], 'wd' => '/'],
        'rpi_issue' => ['cmd' => ['cat', '/boot/issue.txt'], 'wd' => '/'],
        'rpi_config' => ['cmd' => ['cat', '/boot/firmware/config.txt'], 'wd' => '/'],
        'resolv_conf' => ['cmd' => ['cat', '/etc/resolv.conf'], 'wd' => '/'],
        'date' => ['cmd' => ['date'], 'wd' => '/'],
        'ip' => ['cmd' => ['ip', 'a'], 'wd' => '/'],
        'cpu_info' => ['cmd' => ['cat', '/proc/cpuinfo'], 'wd' => '/'],
        'gazeto_client' => ['cmd' => ['git', 'rev-parse', 'HEAD'], 'wd' => '/opt/gazeto-client']
    ];

    $output = [];
    foreach ($commands as $k => $command) {
        $process = new Process($command['cmd']);
        $process->setWorkingDirectory($command['wd']);
        $process->run();
        $output[$k] = $process->getOutput();
    }

    $response = $client->request('POST', $urls['report'], [
        'json' => $output
    ]);
    return $response->getContent(false);
}

function packageUpdate(): string {
    $process = new Process(['apt', 'update']);
    $process->run();
    $outputBuffer = $process->getOutput();

    $process = new Process(['apt', 'upgrade', '-y']);
    $process->run();
    return $outputBuffer . "\r\n" . $process->getOutput();
}

function gazetoUpdate(): string {
    $process = new Process(['git', 'pull']);
    $process->setWorkingDirectory('/opt/gazeto-client');
    $process->run();
    $outputBuffer = $process->getOutput();

    $process = new Process(['ansible-playbook', 'playbook.yml']);
    $process->setWorkingDirectory('/opt/gazeto-client');
    $process->run();

    return $outputBuffer . "\r\n" . $process->getOutput();
}