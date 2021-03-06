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

namespace Rade\DI\Tests\Fixtures;

use Psr\Container\ContainerInterface;
use Rade\DI\AbstractContainer;
use Rade\DI\Container;
use Rade\DI\Services\ServiceProviderInterface;

class OtherServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(AbstractContainer $container, array $configs = []): void
    {
        $container->parameters['other'] = $configs;

        if ($container instanceof Container) {
            $container['other'] = $container;

            return;
        }

        $container->alias('other', ContainerInterface::class);
    }
}
