#!/usr/bin/env bash
set -e


# Ensure composer dependencies installed
if [ ! -d vendor ]; then
composer install --no-interaction --no-scripts
fi


exec "$@"