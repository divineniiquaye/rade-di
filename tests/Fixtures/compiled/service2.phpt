<?php

declare (strict_types=1);

/**
 * @internal This class has been auto-generated by the Rade DI.
 */
class EmptyContainer extends Rade\DI\Container
{
    public array $parameters = [];

    protected static array $privates = [];

    protected array $methodsMap = ['container' => 'getServiceContainer'];

    protected array $types = [Rade\DI\AbstractContainer::class => ['container'], Psr\Container\ContainerInterface::class => ['container'], Rade\DI\Container::class => ['container']];

    protected array $aliases = [];
}
