<?php

declare(strict_types=1);

/*
 * This file is part of DivineNii opensource projects.
 *
 * PHP version 7.4 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2021 DivineNii (https://divinenii.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rade\DI;

use DivineNii\Invoker\CallableReflection;
use DivineNii\Invoker\Exceptions\NotCallableException;
use Nette\SmartObject;
use Psr\Container\ContainerInterface;
use Rade\DI\Exceptions\CircularReferenceException;
use Rade\DI\Exceptions\ContainerResolutionException;
use Rade\DI\Exceptions\FrozenServiceException;
use Rade\DI\Exceptions\NotFoundServiceException;
use Rade\DI\Resolvers\AutowireValueResolver;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Contracts\Service\ResetInterface;

class Container implements \ArrayAccess, ContainerInterface, ResetInterface
{
    use Traits\AutowireTrait;
    use SmartObject;

    protected const WIRING = [
        ContainerInterface::class => [['container']],
        Container::class => [['container']],
    ];

    protected const METHODS_MAP = ['container' => 'getServiceContainer'];

    /**
     * Instantiates the container.
     */
    public function __construct()
    {
        $this->factories = new \SplObjectStorage();
        $this->protected = new \SplObjectStorage();
        $typesWiring     = static::WIRING;

        // Incase this class it extended ...
        if (static::class !== __CLASS__) {
            $typesWiring += [static::class => [['container']]];
        }

        $this->resolver  = new AutowireValueResolver($this, $typesWiring);
    }

    /**
     * Sets a new service to a unique identifier.
     *
     * @param string $offset The unique identifier for the parameter or object
     * @param mixed  $value  The value of the service assign to the $offset
     *
     * @throws FrozenServiceException Prevent override of a frozen service
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value, true);
    }

    /**
     * Gets a registered service definition.
     *
     * @param string $offset The unique identifier for the service
     *
     * @throws NotFoundServiceException If the identifier is not defined
     *
     * @return mixed The value of the service
     */
    public function offsetGet($offset)
    {
        // If alias is set
        $offset = $this->aliases[$offset] ?? $offset;

        // We start by checking if  requested service is internally cached;
        if (isset(static::METHODS_MAP[$offset])) {
            return $this->{static::METHODS_MAP[$offset]}();
        }

        if (!isset($this->keys[$offset])) {
            throw new NotFoundServiceException(sprintf('Identifier "%s" is not defined.', $offset));
        }

        if (
            !\is_object($service = $this->values[$offset]) ||
            isset($this->protected[$service])
        ) {
            return $service;
        }

        return $this->getService($offset, $service);
    }

    /**
     * Checks if a service is set.
     *
     * @param string $offset The unique identifier for the service
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->keys[$this->aliases[$offset] ?? $offset] ?? false;
    }

    /**
     * Unsets a service by given offset.
     *
     * @param string $offset The unique identifier for service definition
     */
    public function offsetUnset($offset): void
    {
        if ($this->offsetExists($offset)) {
            if (\is_object($service = $this->values[$offset])) {
                unset($this->factories[$service], $this->protected[$service]);
            }

            unset($this->values[$offset], $this->frozen[$offset], $this->aliases[$offset], $this->keys[$offset]);
        }
    }

    /**
     * Marks an alias id to service id.
     *
     * @param string $id The alias id
     * @param string $serviceId The registered service id
     *
     * @throws ContainerResolutionException Service id is not found in container
     */
    public function alias(string $id, string $serviceId): void
    {
        if ($id === $serviceId) {
            throw new \LogicException("[{$id}] is aliased to itself.");
        }

        // Incase alias is found linking to another alias that exist
        $serviceId = $this->aliases[$serviceId] ?? $serviceId;

        if (!isset($this[$serviceId])) {
            throw new ContainerResolutionException('Service id is not found in container');
        }

        $this->aliases[$id] = $serviceId;
    }

    /**
     * Assign a set of tags to service(s).
     *
     * @param string[]|string         $serviceIds
     * @param array<int|string,mixed> $tags
     */
    public function tag($serviceIds, array $tags): void
    {
        foreach ((array) $serviceIds as $service) {
            foreach ($tags as $tag => $attributes) {
                // Exchange values if $tag is an integer
                if (\is_int($tmp = $tag)) {
                    $tag = $attributes;
                    $attributes = $tmp;
                }

                $this->tags[$service][$tag] = $attributes;
            }
        }
    }

    /**
     * Resolve all of the bindings for a given tag.
     *
     * @param string $tag
     *
     * @return mixed[] of [service, attributes]
     */
    public function tagged(string $tag): array
    {
        $tags = [];

        foreach ($this->tags as $service => $tagged) {
            if (isset($tagged[$tag])) {
                $tags[] = [$this->get($service), $tagged[$tag]];
            }
        }

        return $tags;
    }

    /**
     * Marks a callable as being a factory service.
     *
     * @param callable $callable A service definition to be used as a factory
     *
     * @throws ContainerResolutionException Service definition has to be a closure or an invokable object
     *
     * @return callable The passed callable
     */
    public function factory($callable): callable
    {
        if (!\is_object($callable) || !\method_exists($callable, '__invoke')) {
            throw new ContainerResolutionException('Service definition is not a Closure or invokable object.');
        }

        $this->factories->attach($callable);

        return $callable;
    }

    /**
     * Protects a callable from being interpreted as a service.
     *
     * This is useful when you want to store a callable as a parameter.
     *
     * @param callable $callable A callable to protect from being evaluated
     *
     * @return callable The passed callable
     *
     * @throws ContainerResolutionException Service definition has to be a closure or an invokable object
     */
    public function protect($callable): callable
    {
        if (!\is_object($callable) || !\method_exists($callable, '__invoke')) {
            throw new ContainerResolutionException('Callable is not a Closure or invokable object.');
        }

        $this->protected->attach($callable);

        return $callable;
    }

    /**
     * Extends an object definition.
     *
     * Useful when you want to extend an existing object definition,
     * without necessarily loading that object.
     *
     * @param string   $id    The unique identifier for the object
     * @param callable $scope A service definition to extend the original
     *
     * @throws NotFoundServiceException If the identifier is not defined
     * @throws FrozenServiceException   If the service is frozen
     *
     * @return mixed The wrapped scope
     */
    public function extend(string $id, callable $scope)
    {
        if (!isset($this->keys[$id])) {
            throw new NotFoundServiceException(sprintf('Identifier "%s" is not defined.', $id));
        }

        if (isset($this->frozen[$id]) || isset(static::METHODS_MAP[$id])) {
            throw new FrozenServiceException($id);
        }

        if (\is_callable($factory = $service = $this->values[$id])) {
            if (isset($this->protected[$service])) {
                throw new ContainerResolutionException(
                    "Protected callable service '{$id}' cannot be extended, cause it has parameters which cannot be resolved."
                );
            }

            $factory = $this->call($factory);
        }

        $extended = $scope(...[$factory, $this]);

        if (\is_object($service) && isset($this->factories[$service])) {
            $this->factories->detach($service);
            $this->factories->attach($extended = fn () => $extended);
        }

        return $this[$id] = $extended;
    }

    /**
     * Returns all defined value names.
     *
     * @return array An array of value names
     */
    public function keys()
    {
        return \array_keys($this->keys);
    }

    /**
     * Resets the container
     */
    public function reset(): void
    {
        foreach ($this->values as $id => $service) {
            if ($service instanceof ResetInterface) {
                $service->reset();
            }

            unset($this->values[$id], $this->keys[$id], $this->frozen[$id]);
        }
        $this->tags = $this->aliases = [];

        $this->protected->removeAll($this->protected);
        $this->factories->removeAll($this->factories);
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        try {
            return $this->offsetGet($id);
        } catch (NotFoundServiceException $serviceError) {
            try {
                return $this->resolver->getByType($id);
            } catch (NotFoundServiceException $typeError) {
                if (\class_exists($id)) {
                    try {
                        return $this->autowireClass($id, []);
                    } catch (ContainerResolutionException $e) {
                    }
                }
            }

            throw $serviceError;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has($id)
    {
        if ($this->offsetExists($id)) {
            return true;
        }

        throw new NotFoundServiceException(sprintf('Identifier "%s" is not defined.', $id));
    }

    /**
     * Set a sevice definition
     *
     * @param mixed $definition
     *
     * @throws FrozenServiceException Prevent override of a frozen service
     */
    public function set(string $id, $definition = null, bool $autowire = false): void
    {
        if (isset($this->frozen[$id]) || isset(static::METHODS_MAP[$id])) {
            throw new FrozenServiceException($id);
        }

        // Incase new service definition exists in aliases.
        unset($this->aliases[$id]);

        // If $id is a valid class name and definition is set to null
        $definition = $definition ?? (\class_exists($id) ? $id : null);

        // Resolving the closure of the service to return it's type hint or class.
        if ($autowire && $this->autowireSupported($definition)) {
            try {
                $type = CallableReflection::create($definition)->getReturnType();
            } catch (NotCallableException $e) {
                $type = \is_object($definition) ? \get_class($definition) : $definition;

                // Create an instance from an class string with autowired arguments
                if (\is_string($definition)) {
                    $definition = $this->autowireClass($definition, []);
                }
            }

            if (null !== $type) {
                $this->autowireService($id, $type);
            }
        }

        $this->values[$id] = $definition;
        $this->keys[$id]   = true;
    }

    /**
     * Registers a service provider.
     *
     * @param ServiceProviderInterface $provider A ServiceProviderInterface instance
     * @param array                    $values   An array of values that customizes the provider
     *
     * @return static
     */
    public function register(ServiceProviderInterface $provider, array $values = [])
    {
        $this->providers[] = $provider;

        if ([] !== $values && $provider instanceof ConfigurationInterface) {
            $providerId = $provider->getName() . '.config';
            $process    = new Processor();

            if (!isset($values[$provider->getName()])) {
                $values = [$provider->getName() => $values];
            }

            $this->values[$providerId] = $process->processConfiguration($provider, $values);
            $this->keys[$providerId]   = true;
        }

        $provider->register($this);

        return $this;
    }

    /**
     * Get the mapped service container instance
     */
    protected function getServiceContainer(): self
    {
        return $this;
    }

    /**
     * @param string          $id
     * @param callable|object $service
     *
     * @return mixed
     */
    private function getService(string $id, $service)
    {
        if (isset($this->loading[$id])) {
            throw new CircularReferenceException(
                \sprintf('Circular reference detected for services: %s.', \implode(', ', \array_keys($this->loading)))
            );
        }

        // Begin checking circular referencing ...
        $this->loading[$id] = true;

        try {
            if (isset($this->frozen[$id])) {
                return $service;
            } elseif (isset($this->factories[$service])) {
                return $this->call($service);
            }

            $this->frozen[$id] = true;

            return $this->values[$id] = \is_callable($service) ? $this->call($service) : $service;
        } finally {
            unset($this->loading[$id]);
        }
    }
}
