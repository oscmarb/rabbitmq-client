FROM php:8.0-cli-alpine3.13

WORKDIR /app

RUN wget https://github.com/FriendsOfPHP/pickle/releases/download/v0.6.0/pickle.phar \
    && mv pickle.phar /usr/local/bin/pickle \
    && chmod +x /usr/local/bin/pickle

RUN apk add --update --no-cache git openssh autoconf rabbitmq-c rabbitmq-c-dev automake make gcc g++ yaml-dev
RUN pecl install yaml
RUN docker-php-ext-enable yaml

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

ADD docker/rabbitmq.sh /root/install-rabbitmq.sh
RUN sh /root/install-rabbitmq.sh

RUN docker-php-ext-enable amqp

ENV PATH /app/bin:/app/vendor/bin:$PATH