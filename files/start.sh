#!/usr/bin/env bash

set -x
set -e

# wait until nginx is started
while ! netstat -tna | grep 'LISTEN\>' | grep -q ':80\>'; do
  sleep 10
done

sleep 10

#chromium-browser --kiosk http://localhost/client
#firefox --display=:0 --kiosk-monitor 0 --private-window --kiosk http://localhost/client

MOZ_ENABLE_WAYLAND=1 firefox --kiosk-monitor 0 --display=:0 --private-window --kiosk http://localhost/client
