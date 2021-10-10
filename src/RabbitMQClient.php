<?php

declare(strict_types=1);

namespace Oscmarb\RabbitMQ;

use AMQPEnvelope;
use AMQPQueue;
use Oscmarb\Ddd\Domain\Message\Message;
use Oscmarb\Ddd\Domain\Message\PublicMessage;
use Oscmarb\RabbitMQ\Utils\MemoryService;

final class RabbitMQClient
{
    public function __construct(private RabbitMQConnection $connection)
    {
    }

    public function publish(string $body, string $exchangeName, string $routingKey, array $metadata = []): void
    {
        $this->connection->exchange($exchangeName)->publish($body, $routingKey, AMQP_NOPARAM, $metadata);
    }

    public function publishMessage(
        Message $message,
        string $exchangeName,
        string $routingKey,
        array $metadata = []
    ): void {
        $publicMessage = PublicMessage::fromMessage($message);
        $this->publish($publicMessage->toJson(), $exchangeName, $routingKey, $metadata);
    }

    public function publishMessageByRoutingConfig(
        Message $message,
        RabbitMQRoutingConfig $routingConfig,
        array $metadata = []
    ): void {
        $queuesData = $routingConfig->queuesDataByMessage($message);

        foreach ($queuesData as $queueData) {
            $this->publishMessage($message, $queueData->exchangeName(), $queueData->queueName(), $metadata);
        }
    }

    public function consume(
        string $queueName,
        \Closure $consumer,
        ?int $maxMessages = null,
        ?int $maxMemoryInMb = null,
        ?\DateTimeImmutable $endsAt = null
    ): void {
        $customConsumer = static function (AMQPEnvelope $envelope, AMQPQueue $queue)
        use ($consumer, &$consumedMessages, $maxMessages, $maxMemoryInMb, $endsAt) {
            $result = $consumer(new RabbitMQMessage($envelope, $queue));

            if (true === isset($result) && false === $result) {
                return false;
            }

            return self::shouldProcessMoreMessages($maxMemoryInMb, $endsAt, ++$consumedMessages, $maxMessages);
        };

        $this->connection->queue($queueName)->consume($customConsumer);
    }

    public function consumeMessage(
        string $queueName,
        \Closure $consumer,
        ?int $maxMessages = null,
        ?int $maxMemoryInMb = null,
        ?\DateTimeImmutable $endsAt = null
    ): void {
        $messageConsumer = function (RabbitMQMessage $message) use ($consumer): bool {
            $publicMessage = PublicMessage::fromJson($message->body());
            $result = $consumer($publicMessage, $message);

            return false === isset($result) || false !== $result;
        };

        $this->consume($queueName, $messageConsumer, $maxMessages, $maxMemoryInMb, $endsAt);
    }

    public function createExchange(string $exchangeName, string $type = \AMQP_EX_TYPE_DIRECT): void
    {
        $exchange = $this->connection->exchange($exchangeName);
        $exchange->setType($type);
        $exchange->setFlags(AMQP_DURABLE);
        $exchange->declareExchange();
    }

    /**
     * @param string $queueName
     * @param string $queueType
     * @param RabbitMQBinding[] $bindings
     * @param string|null $deadLetterExchange
     * @param string|null $deadLetterRoutingKey
     * @param int|null $messageTtl
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     */
    public function createQueue(
        string $queueName,
        array $bindings = [],
        string $queueType = 'classic',
        ?string $deadLetterExchange = null,
        ?string $deadLetterRoutingKey = null,
        ?int $messageTtl = null
    ): void {
        $queue = $this->connection->queue($queueName);

        if (null !== $deadLetterExchange) {
            $queue->setArgument('x-dead-letter-exchange', $deadLetterExchange);
        }

        if (null !== $deadLetterRoutingKey) {
            $queue->setArgument('x-dead-letter-routing-key', $deadLetterRoutingKey);
        }

        if (null !== $messageTtl) {
            $queue->setArgument('x-message-ttl', $messageTtl);
        }

        if (null !== $queueType) {
            $queue->setArgument('x-queue-type', $queueType);
        }

        $queue->setFlags(AMQP_DURABLE);
        $queue->declareQueue();

        foreach ($bindings as $binding) {
            $queue->bind($binding->exchange(), $binding->routingKey(), $binding->arguments());
        }
    }

    public function deleteQueue(string $queueName): void
    {
        $this->connection->queue($queueName)->delete();
    }

    public function deleteExchange(string $exchangeName): void
    {
        $this->connection->exchange($exchangeName)->delete();
    }

    public function disconnect(): void
    {
        $this->connection->disconnect();
    }

    private static function shouldProcessMoreMessages(
        ?int $maxMemoryInMb,
        ?\DateTimeImmutable $endsAt,
        int $consumedMessages,
        ?int $maxMessages
    ): bool {
        if (null !== $maxMessages && $consumedMessages === $maxMessages) {
            return false;
        }

        if (null !== $endsAt && $endsAt < new \DateTimeImmutable()) {
            return false;
        }

        if (null !== $maxMemoryInMb && $maxMemoryInMb < MemoryService::consumedMemoryInMb()) {
            return false;
        }

        return true;
    }
}