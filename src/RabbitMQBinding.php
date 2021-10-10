<?php

declare(strict_types=1);

namespace Oscmarb\RabbitMQ;

final class RabbitMQBinding
{
    public function __construct(private string $exchange, private string $routingKey, private array $arguments = [])
    {
    }

    public function exchange(): string
    {
        return $this->exchange;
    }

    public function routingKey(): string
    {
        return $this->routingKey;
    }

    public function arguments(): array
    {
        return $this->arguments;
    }
}