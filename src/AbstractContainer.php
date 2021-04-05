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
/**
 * Internal shared container.
 *
 * @method call($callback, array $args = [])
 *      Resolve a service definition, class string, invocable object or callable using autowiring.
 * @method resolveClass(string $class, array $args = []) Resolves a class string.
 * @method autowire(string $id, array $types) Resolve wiring classes + interfaces to service id.
 * @method exclude(string $type) Exclude an interface or class type from being autowired.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
abstract class AbstractContainer implements ContainerInterface, ResetInterface
{
    public const IGNORE_MULTIPLE_SERVICE = 0;

    public const EXCEPTION_ON_MULTIPLE_SERVICE = 1;

    /** @var array<string,mixed> For handling a global config around services */
    public array $parameters = [];
}