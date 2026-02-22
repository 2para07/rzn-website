# Use official PHP image
FROM php:8.1-cli

# Set working directory
WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Copy application files
COPY . /app

# Expose port
EXPOSE 8080

# Start PHP development server
CMD ["php", "-S", "0.0.0.0:8080"]
