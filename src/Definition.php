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

use Nette\Utils\{Callback, Reflection};
use PhpParser\Node\{
    Expr\ArrayDimFetch,
    Expr\Assign,
    Expr\BinaryOp,
    Expr\StaticPropertyFetch,
    Name,
    Scalar\String_,
    Stmt\Return_,
    UnionType
};
use PhpParser\BuilderFactory;
use Rade\DI\Exceptions\ServiceCreationException;

/**
 * Represents definition of standard service.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class Definition implements \Stringable
{
    use Traits\ResolveTrait;

    /** Marks a definition as being a factory service. */
    public const FACTORY = 1;

    /** This is useful when you want to autowire a callable or class string lazily. */
    public const LAZY = 2;

    /** Use to check if definition is deprecated. */
    public const DEPRECATED = 3;

    /** Marks a definition as a private service. */
    public const PRIVATE = 4;

    /** Use to check if definition is autowired. */
    public const AUTOWIRED = 5;

    /** Use in second parameter of bind method. */
    public const EXTRA_BIND = '@code@';

    private string $id;

    private bool $factory = false;

    private bool $lazy = false;

    private bool $public = true;

    private array $deprecated = [];

    /**
     * Definition constructor.
     *
     * @param mixed                   $entity
     * @param array<int|string,mixed> $arguments
     */
    public function __construct($entity, array $arguments = [])
    {
        $this->replace($entity, true);
        $this->parameters = $arguments;
    }

    /**
     * The method name generated for a service definition.
     */
    public function __toString(): string
    {
        return 'get' . \str_replace(['.', '_'], '', \ucwords($this->id, '._'));
    }

    /**
     * Attach the missing id and resolver to this definition.
     * NB: This method is used internally and should not be used directly.
     *
     * @internal
     */
    final public function attach(string $id, Resolvers\Resolver $resolver): void
    {
        $this->id = $id;
        $this->resolver = $resolver;
    }

    /**
     * Replace existing entity to a new entity.
     *
     * NB: Using this method must be done before autowiring
     * else autowire manually.
     *
     * @param mixed $entity
     * @param bool  $if     rule matched
     *
     * @return $this
     */
    final public function replace($entity, bool $if): self
    {
        if ($entity instanceof RawDefinition) {
            throw new ServiceCreationException(
                \sprintf('An instance of %s is not a valid definition entity.', RawDefinition::class)
            );
        }

        if ($if /* Replace if matches a rule */) {
            $this->entity = $entity;
        }

        return $this;
    }

    /**
     * Sets the arguments to pass to the service constructor/factory method.
     *
     * @return $this
     */
    final public function args(array $arguments): self
    {
        $this->parameters = $arguments;

        return $this;
    }

    /**
     * Sets/Replace one argument to pass to the service constructor/factory method.
     *
     * @param int|string $key
     * @param mixed      $value
     *
     * @return $this
     */
    final public function arg($key, $value): self
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    /**
     * Sets method, property, Class|@Ref::Method or php code bindings.
     *
     * Binding map method name, property name, mixed type or php code that should be
     * injected in the definition's entity as assigned property, method or
     * extra code added in running that entity.
     *
     * @param string $nameOrMethod A parameter name, a method name, or self::EXTRA_BIND
     * @param mixed  $valueOrRef   The value, reference or statement to bind
     *
     * @return $this
     */
    final public function bind(string $nameOrMethod, $valueOrRef): self
    {
        if (self::EXTRA_BIND === $nameOrMethod) {
            $this->extras[] = $valueOrRef;

            return $this;
        }

        $this->calls[$nameOrMethod] = $valueOrRef;

        return $this;
    }

    /**
     * Enables autowiring.
     *
     * @return $this
     */
    final public function autowire(array $types = []): self
    {
        $this->autowire = true;

        if ([] === $types) {
            if (\is_string($service = $this->entity) && \class_exists($service)) {
                $types = [$service];
            } elseif (\is_callable($service)) {
                $types = Reflection::getReturnTypes(Callback::toReflection($service));
            }
        }

        $this->resolver->autowire($this->id, $types);

        return $this->typeOf($types);
    }

    /**
     * Represents a PHP type-hinted for this definition.
     *
     * @param array|string $types
     *
     * @return $this
     */
    final public function typeOf($types): self
    {
        if (\is_array($types) && (1 === \count($types) || \PHP_VERSION_ID < 80000)) {
            $types = \current($types) ?: null;
        }

        $this->type = $types;

        return $this;
    }

    /**
     * Whether this definition is deprecated, that means it should not be used anymore.
     *
     * @param string $package The name of the composer package that is triggering the deprecation
     * @param string $version The version of the package that introduced the deprecation
     * @param string $message The deprecation message to use
     *
     * @return $this
     */
    final public function deprecate(/* string $package, string $version, string $message */): self
    {
        $args = \func_get_args();

        $message = $args[2] ?? \sprintf('The "%s" service is deprecated. You should stop using it, as it will be removed in the future.', $this->id);

        $this->deprecated['package'] = $args[0] ?? '';
        $this->deprecated['version'] = $args[1] ?? '';
        $this->deprecated['message'] = $message;

        return $this;
    }

    /**
     * Checks if this definition is factory, or lazy type.
     */
    public function is(int $type = self::FACTORY): bool
    {
        if (self::FACTORY === $type) {
            return $this->factory;
        }

        if (self::LAZY === $type) {
            return $this->lazy;
        }

        if (self::DEPRECATED === $type) {
            return (bool) $this->deprecated;
        }

        if (self::PRIVATE === $type) {
            return !$this->public;
        }

        if (self::AUTOWIRED === $type) {
            return $this->autowire;
        }

        return false;
    }

    /**
     * Should the this definition be a type of
     * self::FACTORY|self::PRIVATE|self::LAZY, then set enabled or not.
     *
     * @return $this
     */
    public function should(int $be = self::FACTORY, bool $enabled = true): self
    {
        switch ($be) {
            case self::FACTORY:
                $this->factory = $enabled;

                break;

            case self::LAZY:
                $this->lazy = $enabled;

                break;

            case self::PRIVATE:
                $this->public = !$enabled;

                break;

            case self::PRIVATE | self::FACTORY:
                $this->public = !$enabled;
                $this->factory = $enabled;

                break;

            case self::PRIVATE | self::LAZY:
                $this->public = !$enabled;
                $this->lazy = $enabled;

                break;

            case self::FACTORY | self::LAZY:
                $this->factory = $enabled;
                $this->lazy = $enabled;

                break;

            case self::FACTORY | self::LAZY | self::PRIVATE:
                $this->public = !$enabled;
                $this->factory = $enabled;
                $this->lazy = $enabled;

                break;
        }

        return $this;
    }

    /**
     * Resolves the Definition when in use in ContainerBuilder.
     */
    public function resolve(BuilderFactory $builder): \PhpParser\Node\Expr
    {
        $di = $builder->var('this');

        $arguments = (!$this->lazy || $this->public) ? [$di, (string) $this] : [$di, 'get', [$this->id]];
        $resolved = [$builder, 'methodCall'](...$arguments);

        if ($this->factory) {
            return $resolved;
        }

        return new BinaryOp\Coalesce(
            new ArrayDimFetch(
                new StaticPropertyFetch(new Name('self'), $this->public ? 'services' : 'privates'),
                new String_($this->id)
            ),
            $resolved
        );
    }

    /**
     * Build the definition service.
     *
     * @throws \ReflectionException
     */
    public function build(BuilderFactory $builder): \PhpParser\Builder\Method
    {
        $this->builder = $builder;

        $node = $this->resolveDeprecation($this->deprecated, $builder->method((string) $this)->makeProtected());
        $factory = $this->resolveEntity($this->entity, $this->parameters);

        if (!empty($this->calls + $this->extras)) {
            $node->addStmt(new Assign($resolved = $builder->var($this->public ? 'service' : 'private'), $factory));
            $node = $this->resolveCalls($resolved, $factory, $node);
        }

        if (!empty($types = $this->type)) {
            if (\is_array($types)) {
                $types = new UnionType(\array_map(fn ($type) => new Name($type), $types));
            }

            $node->setReturnType($types);
        }

        if (!$this->factory) {
            $cached = new StaticPropertyFetch(new Name('self'), $this->public ? 'services' : 'privates');
            $resolved = new Assign(new ArrayDimFetch($cached, new String_($this->id)), $resolved ?? $factory);
        }

        return $node->addStmt(new Return_($resolved ?? $factory));
    }
}
