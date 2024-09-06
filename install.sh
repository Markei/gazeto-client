#!/usr/bin/env bash

set -x
set -e

rm -f /boot/gazeto-device-token.txt
echo "$1" > /boot/gazeto-device-token.txt

apt update

apt install -y ansible git

mkdir -p /opt/gazeto-client

git clone https://github.com/Markei/gazeto-client.git /opt/gazeto-client

export ANSIBLE_LOCALHOST_WARNING=False
export ANSIBLE_INVENTORY_UNPARSED_WARNING=False
ansible-playbook /opt/gazeto-client/playbook.yml

systemctl reboot