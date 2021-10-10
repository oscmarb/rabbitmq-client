<?php

declare(strict_types=1);

namespace Oscmarb\RabbitMQ\Utils;

final class MemoryService
{
    public static function consumedMemoryInMb(): float
    {
        return round(memory_get_usage() / 1048576, 2);
    }
}