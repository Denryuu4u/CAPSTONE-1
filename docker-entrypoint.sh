#!/bin/sh
# Runtime startup for the Apache container. Runs on EVERY container start, so it
# is immune to Docker/Railway build-layer caching.
set -e

echo "[entrypoint] MPM before: $(ls /etc/apache2/mods-enabled/ | grep -i mpm | tr '\n' ' ')"

# Guarantee exactly ONE Apache MPM (prefork — required by mod_php):
#  1) strip any hard-coded 'LoadModule mpm_*' lines from the main config,
#  2) remove every MPM symlink from mods-enabled,
#  3) enable only prefork.
sed -i '/LoadModule mpm_.*_module/d' /etc/apache2/apache2.conf 2>/dev/null || true
rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf
a2enmod mpm_prefork rewrite >/dev/null 2>&1 || true

echo "[entrypoint] MPM after:  $(ls /etc/apache2/mods-enabled/ | grep -i mpm | tr '\n' ' ')"

# Railway injects $PORT; make Apache listen on it (default 80 for local runs).
sed -i "s/^Listen 80/Listen ${PORT:-80}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT:-80}>/" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
