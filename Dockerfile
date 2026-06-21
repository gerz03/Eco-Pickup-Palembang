FROM php:8.1-cli

# Install required PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /app

# Copy application files
COPY . .

# Create uploads directory if needed
RUN mkdir -p /app/uploads/waste && \
    chmod -R 755 /app/uploads

# Set proper permissions
RUN chmod -R 755 /app

# Enable error reporting for debugging
RUN find /etc/php* -name "php.ini" -exec sh -c 'echo "display_errors = On" >> "$1"; echo "error_log = /dev/stderr" >> "$1"' _ {} \; || true

# Expose port
EXPOSE 8080

# Start PHP built-in server
CMD ["php", "-S", "0.0.0.0:${PORT:-8080}", "-t", "/app"]
