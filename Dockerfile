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

# Copy the app into Apache's document root.
COPY . /var/www/html/

# Let Apache (www-data) write uploaded files.
RUN chown -R www-data:www-data /var/www/html/uploads 2>/dev/null || true

# Runtime entrypoint: fixes the Apache MPM and $PORT on EVERY start (immune to
# build caching). See docker-entrypoint.sh. Dedicated COPY so edits bust cache.
COPY docker-entrypoint.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh
CMD ["/usr/local/bin/start.sh"]
