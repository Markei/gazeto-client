<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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
        'gazeto_client' => ['cmd' => ['git', 'rev-parse', 'HEAD'], 'wd' => '/opt/gazeto-client'],
        'wayfire' => ['cmd' => ['cat', '/home/pi/.config/wayfire.ini'], 'wd' => '/']
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
    $process = new Process(['dpkg', '--configure', '-a']);
    $process->setTimeout(null);
    $process->run();
    $outputBuffer = $process->getOutput();

    $process = new Process(['apt', 'update']);
    $process->setTimeout(null);
    $process->run();
    $outputBuffer = $outputBuffer . "\r\n" . $process->getOutput();

    $process = new Process(['apt', 'upgrade', '-y']);
    $process->setTimeout(null);
    $process->run();
    $outputBuffer = $outputBuffer . "\r\n" . $process->getOutput();

    return $outputBuffer;
}

function gazetoUpdate(): string {
    $process = new Process(['git', 'pull']);
    $process->setTimeout(null);
    $process->setWorkingDirectory('/opt/gazeto-client');
    $process->run();
    $outputBuffer = $process->getOutput();

    $process = new Process(['ansible-playbook', 'playbook.yml']);
    $process->setTimeout(null);
    $process->setWorkingDirectory('/opt/gazeto-client');
    $process->run();

    return $outputBuffer . "\r\n" . $process->getOutput();
}

function refreshUrls(): string {
    global $client, $urls;
    assert($client instanceof HttpClientInterface);

    $response = $client->request('POST', 'https://www.markeigazeto.nl/api/display', [
        'body' => [
            'showUrl' => $urls['show']
        ]
    ]);

    $data = $response->toArray();
    file_put_contents('/boot/gazeto-urls.json', json_encode($data['urls']));

    return $response->getContent();
}

function writeScreenConfiguration(): string {
    global $client, $urls;
    assert($client instanceof HttpClientInterface);

    file_put_contents('/home/pi/.config/wayfire.ini_bak' . time(), file_get_contents('/home/pi/.config/wayfire.ini'));

    $iniFile = parse_ini_file('/home/pi/.config/wayfire.ini', true, INI_SCANNER_RAW);

    $displayConfig = $client->request('GET', $urls['settings'])->toArray();

    foreach(['output:HDMI-A-1', 'output:HDMI-A-2'] as $key) {
        if (isset($iniFile[$key]) === false) {
            $iniFile[$key] = [];
        }

        if (isset($displayConfig['resolutionAndFrequency']) && !empty($displayConfig['resolutionAndFrequency'])) {
            $iniFile[$key]['mode'] = $displayConfig['resolutionAndFrequency'];
        }
        if (isset($displayConfig['transform']) && !empty($displayConfig['transform'])) {
            $iniFile[$key]['transform'] = $displayConfig['transform'];
        }
        if (isset($displayConfig['zoomLevel']) && !empty($displayConfig['zoomLevel'])) {
            $iniFile[$key]['scale'] = $displayConfig['zoomLevel'];
        }
    }

    $iniOutput = '';
    foreach ($iniFile as $section => $options) {
        $iniOutput .= '[' . $section . ']' . PHP_EOL;
        foreach ($options as $k => $v) {
            $iniOutput .= $k . ' = ' . $v . PHP_EOL;
        }
        $iniOutput .= PHP_EOL;
    }
    file_put_contents('/home/pi/.config/wayfire.ini', $iniOutput);

    return $iniOutput;
}