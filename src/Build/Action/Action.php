<?php

declare(strict_types=1);

namespace Duyler\Framework\Build\Action;

use Closure;
use Duyler\Framework\Build\AttributeInterface;
use UnitEnum;

class Action
{
    private static ActionBuilder $builder;
    private string|UnitEnum $id;
    private string | Closure $handler;
    private array $require = [];
    private array $config = [];
    private array $alternates = [];
    private ?string $argument = null;
    private null | string | Closure $argumentFactory = null;
    private ?string $contract = null;
    private null | string | Closure $rollback = null;
    private bool $externalAccess = true;
    private bool $repeatable = false;
    private bool $lock = true;
    private int $retries = 0;
    private null|string|UnitEnum $listen = null;
    private bool $private = false;
    private array $sealed = [];
    private bool $silent = false;

    /** @var array<string|int, mixed> */
    private array $labels = [];

    /** @var AttributeInterface[] */
    private array $attributes = [];

    public function __construct(ActionBuilder $builder)
    {
        static::$builder = $builder;
    }

    public static function build(string|UnitEnum $id, string|Closure $handler): self
    {
        $action = new self(static::$builder);
        $action->id = $id;
        $action->handler = $handler;

        self::$builder->addAction($action);

        return $action;
    }

    public function require(string|UnitEnum ...$require): self
    {
        $this->require = $require;
        return $this;
    }

    public function config(array $config): self
    {
        $this->config = $config;
        return $this;
    }

    public function alternates(string|UnitEnum ...$alternates): self
    {
        $this->alternates = $alternates;
        return $this;
    }

    public function retries(int $retries): self
    {
        $this->retries = $retries;
        return $this;
    }

    public function argument(string $argument): self
    {
        $this->argument = $argument;
        return $this;
    }

    public function argumentFactory(string| Closure $argumentFactory): self
    {
        $this->argumentFactory = $argumentFactory;
        return $this;
    }

    public function contract(string $contract): self
    {
        $this->contract = $contract;
        return $this;
    }

    public function rollback(string|Closure $rollback): self
    {
        $this->rollback = $rollback;
        return $this;
    }

    public function externalAccess(bool $externalAccess): self
    {
        $this->externalAccess = $externalAccess;
        return $this;
    }

    public function repeatable(bool $repeatable): self
    {
        $this->repeatable = $repeatable;
        return $this;
    }

    public function lock(bool $lock): self
    {
        $this->lock = $lock;
        return $this;
    }

    public function listen(string|UnitEnum $listen): self
    {
        $this->listen = $listen;
        return $this;
    }

    public function private(bool $private): self
    {
        $this->private = $private;
        return $this;
    }

    public function sealed(string|UnitEnum ...$sealed): self
    {
        $this->sealed = $sealed;
        return $this;
    }

    public function silent(bool $silent): self
    {
        $this->silent = $silent;
        return $this;
    }

    public function labels(array $labels): self
    {
        $this->labels = $labels;
        return $this;
    }

    public function attributes(AttributeInterface ...$attributes): self
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function get(string $property): mixed
    {
        return $this->{$property};
    }
}
