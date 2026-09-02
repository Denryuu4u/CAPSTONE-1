# Vast Solutions — PHP + Apache image.
# Mirrors the local XAMPP/Apache setup: every .php page is served directly from
# the document root, so the app lives at "/" (pair with BASE_URL="" on Railway).
FROM php:8.2-apache

# System libs + PHP extensions the app needs:
#   pdo_mysql -> db() PDO connection
#   mbstring  -> mb_encode_mimeheader() in includes/mailer.php (needs libonig-dev)
RUN apt-get update \
 && apt-get install -y --no-install-recommends libonig-dev \
 && docker-php-ext-install pdo_mysql mbstring \
 && rm -rf /var/lib/apt/lists/*

# Apache must load exactly ONE MPM. The apt layer can pull in mpm_event next to
# the image's mpm_prefork, which crashes with "More than one MPM loaded".
# Force prefork only, then (re)enable rewrite. Use ';' so a no-op disable is fine.
RUN a2dismod mpm_event mpm_worker 2>/dev/null; a2enmod mpm_prefork rewrite

# Copy the app into Apache's document root.
COPY . /var/www/html/

# Let Apache (www-data) write uploaded files.
RUN chown -R www-data:www-data /var/www/html/uploads 2>/dev/null || true

# Railway injects $PORT at runtime; point Apache at it (defaults to 80 locally).
CMD ["/bin/sh","-c","sed -i \"s/^Listen 80/Listen ${PORT:-80}/\" /etc/apache2/ports.conf && sed -i \"s/:80>/:${PORT:-80}>/\" /etc/apache2/sites-available/000-default.conf && exec apache2-foreground"]
