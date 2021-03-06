<?php

declare (strict_types=1);

/**
 * @internal This class has been auto-generated by the Rade DI.
 */
class LazyContainer extends Rade\DI\Container
{
    protected static array $privates;

    public array $parameters = [];

    protected array $methodsMap = ['service_1' => 'getService1', 'service_2' => 'getService2', 'service_test' => 'getServiceTest'];

    protected array $types = [Psr\Container\ContainerInterface::class => ['container'], Rade\DI\AbstractContainer::class => ['container'], Rade\DI\Container::class => ['container']];

    protected array $aliases = [];

    public function __construct()
    {
        parent::__construct();
        self::$privates = [];
    }

    protected function getService1(): Rade\DI\Tests\Fixtures\Service
    {
        return self::$services['service_1'] = $this->resolver->resolve(Rade\DI\Tests\Fixtures\Service::class);
    }

    protected function getService2(): Rade\DI\Tests\Fixtures\Service
    {
        return $this->resolver->resolve(Rade\DI\Tests\Fixtures\Service::class);
    }

    protected function getService3(): Rade\DI\Tests\Fixtures\Service
    {
        return self::$privates['service_3'] = $this->resolver->resolve(Rade\DI\Tests\Fixtures\Service::class);
    }

    protected function getService4(): Rade\DI\Tests\Fixtures\Service
    {
        return $this->resolver->resolve(Rade\DI\Tests\Fixtures\Service::class);
    }

    protected function getServiceTest(): Rade\DI\Tests\Fixtures\Constructor
    {
        $service = new Rade\DI\Tests\Fixtures\Constructor($this);
        $service->value = $this->getService4();

        return self::$services['service_test'] = $service;
    }
}
