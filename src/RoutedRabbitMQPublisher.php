<?php

declare(strict_types=1);

namespace Oscmarb\RabbitMQ;

use Oscmarb\Ddd\Domain\Message\Message;

final class RoutedRabbitMQPublisher
{
    private RabbitMQClient $client;

    public function __construct(RabbitMQConnection $connection, private RabbitMQRoutingConfig $routingConfig)
    {
        $this->client = new RabbitMQClient($connection);
    }

    public function publish(Message $message, array $metadata = []): void
    {
        foreach ($this->routingConfig->queuesDataByMessage($message) as $queueData) {
            $this->client->publishMessage($message, $queueData->exchangeName(), $queueData->queueName(), $metadata);
        }
    }
}