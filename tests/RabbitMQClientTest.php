<?php

declare(strict_types=1);

namespace Oscmarb\RabbitMQ\Tests;

use Oscmarb\RabbitMQ\RabbitMQBinding;
use Oscmarb\RabbitMQ\RabbitMQClient;
use Oscmarb\RabbitMQ\RabbitMQMessage;
use PHPUnit\Framework\TestCase;

final class RabbitMQClientTest extends TestCase
{
    private const EXCHANGE_NAME = 'exchange_test';
    private const QUEUE_NAME = 'queue_test';

    private RabbitMQClient $client;

    protected function setUp(): void
    {
        $this->client = new RabbitMQClient(RabbitMQConnectionFactory::create());

        $this->client->createExchange(self::EXCHANGE_NAME);
        $this->client->createQueue(self::QUEUE_NAME, [new RabbitMQBinding(self::EXCHANGE_NAME, self::QUEUE_NAME)]);
    }

    protected function tearDown(): void
    {
        $this->clearEnvironment();
    }

    public function test_should_publish_and_consume_message(): void
    {
        $message = 'aMessage';

        $this->client->publish($message, self::EXCHANGE_NAME, self::QUEUE_NAME);

        $this->client->consume(
            self::QUEUE_NAME,
            fn(RabbitMQMessage $rabbitMqMessage) => self::assertEquals($message, $this->consumer($rabbitMqMessage)),
            1,
            null,
            (new \DateTimeImmutable())->add(\DateInterval::createFromDateString('5 seconds'))
        );
    }

    private function consumer(RabbitMQMessage $rabbitMqMessage): string
    {
        $rabbitMqMessage->ack();

        return $rabbitMqMessage->body();
    }

    private function clearEnvironment(): void
    {
        $this->client->deleteQueue(self::QUEUE_NAME);
        $this->client->deleteExchange(self::EXCHANGE_NAME);
    }
}