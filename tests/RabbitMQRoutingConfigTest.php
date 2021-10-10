<?php

declare(strict_types=1);

namespace Oscmarb\RabbitMQ\Tests;

use Oscmarb\RabbitMQ\RabbitMQQueueData;
use Oscmarb\RabbitMQ\RabbitMQRoutingConfig;
use PHPUnit\Framework\TestCase;

final class RabbitMQRoutingConfigTest extends TestCase
{
    public function test_should_return_expected_routing_config(): void
    {
        $config = new RabbitMQRoutingConfig(__DIR__.'/test_config.yaml');
        $randomDomainEvent = new DomainEventMock();
        $randomCommand = new CommandMock();

        $expectedDomainEventQueuesData = [new RabbitMQQueueData('default_exchange', 'default_queue')];
        $expectedCommandQueuesData = [
            new RabbitMQQueueData('command_exchange', 'first_command_queue'),
            new RabbitMQQueueData('command_exchange', 'second_command_queue'),
            new RabbitMQQueueData('default_command_exchange', 'command_queue'),
        ];

        self::assertEquals($expectedDomainEventQueuesData, $config->queuesDataByMessage($randomDomainEvent));
        self::assertEquals($expectedCommandQueuesData, $config->queuesDataByMessage($randomCommand));
        self::assertEquals(
            [
                ...$expectedDomainEventQueuesData,
                ...$expectedCommandQueuesData,
            ],
            $config->allQueuesData()
        );
    }
}