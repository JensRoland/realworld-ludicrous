FROM dunglas/frankenphp:latest-php8.3

# Install extensions here
# - Xdebug for profiling
# - OPcache for performance
RUN install-php-extensions \
    xdebug \
	opcache

# Configure Xdebug for profiling
RUN echo "xdebug.mode=profile" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=trigger" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.output_dir=/app/profiling" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.profiler_output_name=cachegrind.out.%R.%u" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Configure OPcache for better performance
# Using validate_timestamps=1 for development (see code changes immediately)
# For production, set validate_timestamps=0 and use preloading
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.file_cache=/tmp/opcache" >> /usr/local/etc/php/conf.d/opcache.ini \
    && mkdir -p /tmp/opcache

# Create profiling directory
RUN mkdir -p /app/profiling && chmod 777 /app/profiling
