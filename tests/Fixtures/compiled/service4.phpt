<?php

declare (strict_types=1);

/**
 * @internal This class has been auto-generated by the Rade DI.
 */
class FactoryContainer extends Rade\DI\Container
{
    public array $parameters = [];

    protected static array $privates = [];

    protected array $methodsMap = ['service_1' => 'getService1', 'service_2' => 'getService2', 'container' => 'getServiceContainer'];

    protected array $types = [Rade\DI\AbstractContainer::class => ['container'], Psr\Container\ContainerInterface::class => ['container'], Rade\DI\Container::class => ['container']];

    protected array $aliases = [];

    protected function getService1(): Rade\DI\Tests\Fixtures\Service
    {
        return new Rade\DI\Tests\Fixtures\Service();
    }

    protected function getService2(): Rade\DI\Tests\Fixtures\Service
    {
        return new Rade\DI\Tests\Fixtures\Service();
    }
}
