<?php

declare (strict_types=1);

/**
 * @internal This class has been auto-generated by the Rade DI.
 */
class InjectableContainer extends Rade\DI\Container
{
    public array $parameters = [];

    protected array $methodsMap = ['bar' => 'getBar', 'foo' => 'getFoo', 'inject' => 'getInject'];

    protected array $types = [Psr\Container\ContainerInterface::class => ['container'], Rade\DI\AbstractContainer::class => ['container'], Rade\DI\Container::class => ['container'], Rade\DI\Tests\Fixtures\Constructor::class => ['bar'], Rade\DI\Tests\Fixtures\FooClass::class => ['foo']];

    protected array $aliases = [];

    protected function getBar(): Rade\DI\Tests\Fixtures\Constructor
    {
        return self::$services['bar'] = new Rade\DI\Tests\Fixtures\Constructor($this);
    }

    protected function getFoo(): Rade\DI\Tests\Fixtures\FooClass
    {
        return self::$services['foo'] = new Rade\DI\Tests\Fixtures\FooClass();
    }

    protected function getInject(): Rade\DI\Tests\Fixtures\InjectableClass
    {
        $service = new Rade\DI\Tests\Fixtures\InjectableClass();
        $service->service = self::$services['bar'] ?? $this->getBar();
        $service->injectFooClass(self::$services['foo'] ?? $this->getFoo());

        return self::$services['inject'] = $service;
    }
}
