<?php

declare(strict_types=1);

namespace Oscmarb\RabbitMQ;

final class RabbitMQQueueData
{
    public function __construct(private string $exchangeName, private string $queueName)
    {
    }

    public function exchangeName(): string
    {
        return $this->exchangeName;
    }

    public function queueName(): string
    {
        return $this->queueName;
    }
}