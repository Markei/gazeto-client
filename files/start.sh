#!/usr/bin/env bash

set -x
set -e

sleep 5
#chromium-browser --kiosk http://localhost/client
#firefox --display=:0 --kiosk-monitor 0 --private-window --kiosk http://localhost/client
MOZ_ENABLE_WAYLAND=1 firefox --kiosk-monitor 0 --display=:0 --private-window --kiosk http://localhost/client
