<?php

declare (strict_types=1);

/**
 * @internal This class has been auto-generated by the Rade DI.
 */
class CompiledContainer extends Rade\DI\Container
{
    public array $parameters = [];

    protected array $methodsMap = ['bar' => 'getBar'];

    protected array $types = [Psr\Container\ContainerInterface::class => ['container'], Rade\DI\AbstractContainer::class => ['container'], Rade\DI\Container::class => ['container']];

    protected array $aliases = [];

    protected function getBar(): Rade\DI\Tests\Fixtures\Bar
    {
        return self::$services['bar'] = new Rade\DI\Tests\Fixtures\Bar('value', new Rade\DI\Tests\Fixtures\Service(), null, [1, 2, 3], []);
    }
}
