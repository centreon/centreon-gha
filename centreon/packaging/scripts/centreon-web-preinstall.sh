#!/bin/bash

# Prepare php upgrade
if systemctl --all --type service | grep -q "php8.0-fpm" ; then
  systemctl disable php8.0-fpm
  systemctl stop php8.0-fpm
fi
