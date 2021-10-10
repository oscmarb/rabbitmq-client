<?php

declare(strict_types=1);

namespace Oscmarb\RabbitMQ;

final class RabbitMQConnection
{
    private ?\AMQPConnection $connection = null;
    private ?\AMQPChannel $channel = null;

    /** @var \AMQPExchange[] */
    private array $exchanges = [];

    /** @var \AMQPQueue[] */
    private array $queues = [];

    public function __construct(private array $configuration)
    {
    }

    public function queue(string $name): \AMQPQueue
    {
        if (false === array_key_exists($name, $this->queues)) {
            $queue = new \AMQPQueue($this->channel());
            $queue->setName($name);

            $this->queues[$name] = $queue;
        }

        return $this->queues[$name];
    }

    public function exchange(string $name): \AMQPExchange
    {
        if (false === array_key_exists($name, $this->exchanges)) {
            $exchange = new \AMQPExchange($this->channel());
            $exchange->setName($name);

            $this->exchanges[$name] = $exchange;
        }

        return $this->exchanges[$name];
    }

    private function channel(): \AMQPChannel
    {
        if (true !== $this->channel?->isConnected()) {
            $this->channel = new \AMQPChannel($this->connection());
        }

        return $this->channel;
    }

    private function connection(): \AMQPConnection
    {
        $this->connection ??= new \AMQPConnection($this->configuration);

        if (false === $this->connection->isConnected()) {
            $this->connection->pconnect();
        }

        return $this->connection;
    }

    public function disconnect(): void
    {
        if (true === $this->connection?->isConnected()) {
            $this->connection->disconnect();
        }
    }
}