FROM composer:2.0

RUN apk add --update --no-cache icu && \
    apk add --update --no-cache --virtual .build-dependencies $PHPIZE_DEPS icu-dev && \
    docker-php-ext-install intl
