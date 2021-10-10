<?php

declare(strict_types=1);

namespace Oscmarb\RabbitMQ\Tests;

use Oscmarb\RabbitMQ\RabbitMQConnection;

final class RabbitMQConnectionFactory
{
    private static ?RabbitMQConnection $connection = null;

    public static function create(): RabbitMQConnection
    {
        return self::$connection ??= new RabbitMQConnection(
            [
                'host' => \getenv('RABBITMQ_HOST'),
                'port' => \getenv('RABBITMQ_PORT'),
                'login' => \getenv('RABBITMQ_DEFAULT_USER'),
                'password' => \getenv('RABBITMQ_DEFAULT_PASS'),
            ]
        );
    }
}