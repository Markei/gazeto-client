#!/usr/bin/env bash

set -x
set -e

cd /opt/gazeto-client

git pull

export ANSIBLE_LOCALHOST_WARNING=False
export ANSIBLE_INVENTORY_UNPARSED_WARNING=False
ansible-playbook playbook.yml

cd client-controller
composer install