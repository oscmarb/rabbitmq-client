<?php

declare(strict_types=1);

namespace Oscmarb\RabbitMQ;

final class RabbitMQMessage
{
    public function __construct(private \AMQPEnvelope $envelope, private \AMQPQueue $queue)
    {
    }

    public function body(): string
    {
        return $this->envelope->getBody();
    }

    public function ack(): void
    {
        $this->queue->ack($this->envelope->getDeliveryTag());
    }

    public function envelope(): \AMQPEnvelope
    {
        return $this->envelope;
    }
}