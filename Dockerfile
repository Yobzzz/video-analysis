FROM php:8.2-fpm

# 安装系统依赖 + Nginx + Supervisor
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        git \
        unzip \
        libzip-dev \
        libx264-dev \
        curl \
        ca-certificates \
        ffmpeg \
    && docker-php-ext-install -j$(nproc) zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 安装 Node.js 18（用于抖音 a_bogus 签名）
COPY --from=node:18-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:18-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# 安装 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# 先复制依赖文件，利用 Docker 缓存层
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# 复制应用代码
COPY . .

# 生成 autoloader（含项目类）
RUN composer dump-autoload --optimize

# 配置 Nginx
RUN rm -f /etc/nginx/sites-enabled/default \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log
COPY docker/nginx.conf /etc/nginx/sites-enabled/default

# 配置 PHP-FPM
RUN sed -i 's/^listen = .*/listen = 127.0.0.1:9000/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i '/^listen.allowed_clients/d' /usr/local/etc/php-fpm.d/www.conf \
    && echo 'php_admin_value[error_log] = /dev/stderr' >> /usr/local/etc/php-fpm.d/www.conf \
    && echo 'php_admin_value[max_execution_time] = 900' >> /usr/local/etc/php-fpm.d/www.conf \
    && echo 'php_admin_value[max_input_time] = 900' >> /usr/local/etc/php-fpm.d/www.conf \
    && echo 'php_admin_value[upload_max_filesize] = 100M' >> /usr/local/etc/php-fpm.d/www.conf \
    && echo 'php_admin_value[post_max_size] = 120M' >> /usr/local/etc/php-fpm.d/www.conf \
    && echo 'php_admin_flag[log_errors] = on' >> /usr/local/etc/php-fpm.d/www.conf

# 配置 Supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/app.conf

# 权限
RUN mkdir -p storage/ \
    && chmod -R 755 storage/ \
    && chown -R www-data:www-data storage/

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://127.0.0.1/api/v1/health || exit 1

CMD ["supervisord", "-c", "/etc/supervisor/conf.d/app.conf"]
