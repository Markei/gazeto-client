
# Gazeto Client

With this repository you can transform your Raspberry Pi into a full functional client for the Markei Gazeto digital signage software.

## Requirements

Raspberry PI 4 or 5 with 8GB of memory. Install Raspberry PI 64 bit OS with desktop interface. Use username pi. Configure network/wifi, SSH, VNC what you like. It is important to enable Wayland via Wayfire.

Log in to your Markei Gazeto account go to Displays and click Install. You will find the install command needed to install this software. For example:

    curl -s https://raw.githubusercontent.com/Markei/gazeto-client/main/install.sh | sudo bash -s 000-AAA

After the installation is done the Raspberry is rebooted and ready to pair with your Gazeto account.

## Open Source

This software is open source and depends on a lot of other open source packages: Debian, Raspberry PI OS, Ansible, PHP, Composer, Symfony, Mozilla Firefox, Chromium, Git and many others.

Markei Gazeto digital signange software is a non free service, but this client software is. We published it so you can use this client software for your own project.

License: MIT
