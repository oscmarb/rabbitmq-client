<?php

declare(strict_types=1);

namespace Oscmarb\RabbitMQ;

use Oscmarb\Ddd\Domain\Message\Message;
use Oscmarb\RabbitMQ\Exception\InvalidRabbitMQRoutingConfigurationException;

final class RabbitMQRoutingConfig
{
    private const DEFAULT = 'default';
    private const EXCHANGE = 'exchange';
    private const QUEUES = 'queues';
    private const QUEUE = 'queue';

    private ?array $config = null;

    public function __construct(private string $configPath)
    {
    }

    /**
     * @param Message $message
     * @return RabbitMQQueueData[]
     */
    public function queuesDataByMessage(Message $message): array
    {
        $this->config ??= yaml_parse_file($this->configPath);
        $messageTypeConfig = $this->config[$message->messageType()] ?? [];

        $defaultConfig = $messageTypeConfig[self::DEFAULT] ?? [];
        $messageConfig = $messageTypeConfig[$message::class] ?? $defaultConfig;

        if (true === empty($messageConfig)) {
            return [];
        }

        if (true === isset($messageConfig[self::QUEUE]) || true === isset($messageConfig[self::QUEUES]) || true === isset($messageConfig[self::EXCHANGE])) {
            $messageConfig = [$messageConfig];
        }

        $queuesData = [];

        foreach ($messageConfig as $routingConfig) {
            $queuesData = [
                ...$queuesData,
                ...$this->extractQueuesDataFromRoutingConfig($routingConfig, $defaultConfig),
            ];
        }

        return $queuesData;
    }

    /**
     * @return RabbitMQQueueData[]
     * @throws \Throwable
     */
    public function allQueuesData(): array
    {
        $queuesData = [];
        $this->config ??= yaml_parse_file($this->configPath);
        $messageTypes = \array_keys($this->config);

        foreach ($messageTypes as $messageType) {
            $messageTypeConfig = $this->config[$messageType] ?? [];
            $defaultConfig = $messageTypeConfig[self::DEFAULT] ?? [];

            foreach ($messageTypeConfig as $routingKey => $routingConfigs) {
                if (true === isset($routingConfigs[self::QUEUE]) || true === isset($routingConfigs[self::QUEUES]) || true === isset($routingConfigs[self::EXCHANGE])) {
                    $routingConfigs = [$routingConfigs];
                }

                foreach ($routingConfigs as $routingConfig) {
                    try {
                        $queuesData = [
                            ...$queuesData,
                            ...$this->extractQueuesDataFromRoutingConfig($routingConfig, $defaultConfig),
                        ];
                    } catch (\Throwable $throwable) {
                        if (self::DEFAULT !== $routingKey) {
                            throw  $throwable;
                        }
                    }
                }
            }
        }

        return $queuesData;
    }

    private function extractQueuesDataFromRoutingConfig(array $routingConfig, array $defaultConfig): array
    {
        $exchangeName = $routingConfig[self::EXCHANGE] ?? $defaultConfig[self::EXCHANGE] ?? null;
        $queuesNames = $routingConfig[self::QUEUES]
            ?? $routingConfig[self::QUEUE]
            ?? $defaultConfig[self::QUEUES]
            ?? $defaultConfig[self::QUEUE]
            ?? null;

        if (true === empty($exchangeName) || true === empty($queuesNames)) {
            throw new InvalidRabbitMQRoutingConfigurationException();
        }

        return \array_map(
            static fn(string $queueName) => new RabbitMQQueueData($exchangeName, $queueName),
            true === \is_array($queuesNames) ? $queuesNames : [$queuesNames]
        );
    }
}