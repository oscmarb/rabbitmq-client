<?php

declare(strict_types=1);

namespace Oscmarb\RabbitMQ\Tests;

use Oscmarb\Ddd\Domain\Command\Command;

final class CommandMock extends Command
{
    public static function commandName(): string
    {
        return 'mock';
    }

    public static function fromPrimitives(mixed $body, string $messageId, string $messageOccurredOn)
    {
        return new self($messageId, $messageOccurredOn);
    }

    public function toPrimitives(): array
    {
        return [];
    }
}