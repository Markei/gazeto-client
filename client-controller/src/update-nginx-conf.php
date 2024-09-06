<?php

$displayToken = '';
if (file_exists('/boot/gazeto-display-token.txt')) {
    $displayToken = file_get_contents('/boot/gazeto-display-token.txt');
}

$showUrl = '';
if (file_exists('/boot/gazeto-urls.json')) {
    $showUrl = json_decode(file_get_contents('/boot/gazeto-urls.json'), true)['show'];
}

file_put_contents('/etc/nginx/sites-available/default', str_replace([
    '__DISPLAYTOKEN__',
    '__SHOWURL__'
], [
    $displayToken,
    $showUrl
], file_get_contents('/etc/nginx/default.template')));