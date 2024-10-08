<?php

declare(strict_types=1);

namespace Duyler\Builder\Build\State;

use Duyler\EventBus\Build\Context;
use Duyler\EventBus\BusBuilder;

class StateContext
{
    private static BusBuilder $busBuilder;

    public function __construct(BusBuilder $busBuilder)
    {
        self::$busBuilder = $busBuilder;
    }

    /** @param array<array-key, string> $scope */
    public static function add(array $scope): void
    {
        self::$busBuilder->addStateContext(
            new Context(
                $scope,
            ),
        );
    }
}
