FROM php:8.1-cli

# Install required PHP extensions and tools
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /app

# Copy application files
COPY . .

# Create necessary directories with proper permissions
RUN mkdir -p /app/uploads/waste && \
    mkdir -p /app/logs && \
    chmod -R 755 /app && \
    chmod -R 777 /app/uploads && \
    chmod -R 777 /app/logs

# Create .env if not exists
RUN if [ ! -f /app/.env ]; then cp /app/.env.example /app/.env 2>/dev/null || echo "Creating minimal .env..."; fi

# Expose port
EXPOSE 8080

# Start PHP built-in server with error handling
CMD ["sh", "-c", "echo 'Starting PHP server on port 8080...' && php -d display_errors=1 -d error_log=/dev/stderr -S 0.0.0.0:${PORT:-8080} -t /app 2>&1"]
