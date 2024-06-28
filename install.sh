#!/usr/bin/env bash

set -x
set -e

apt update

apt install -y ansible git

mkdir -p /opt/gazeto-client

git clone https://github.com/Markei/gazeto-client.git /opt/gazeto-client

ansible-playbook /opt/gazeto-client/playbook.yml