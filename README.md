# The PHP Rade DI

[![Latest Version](https://img.shields.io/packagist/v/divineniiquaye/rade-di.svg?style=flat-square)](https://packagist.org/packages/divineniiquaye/rade-di)
[![Software License](https://img.shields.io/badge/License-BSD--3-brightgreen.svg?style=flat-square)](LICENSE)
[![Workflow Status](https://img.shields.io/github/workflow/status/divineniiquaye/rade-di/Tests?style=flat-square)](https://github.com/divineniiquaye/rade-di/actions?query=workflow%3ATests)
[![Code Maintainability](https://img.shields.io/codeclimate/maintainability/divineniiquaye/rade-di?style=flat-square)](https://codeclimate.com/github/divineniiquaye/rade-di)
[![Coverage Status](https://img.shields.io/codecov/c/github/divineniiquaye/rade-di?style=flat-square)](https://codecov.io/gh/divineniiquaye/rade-di)
[![Quality Score](https://img.shields.io/scrutinizer/g/divineniiquaye/rade-di.svg?style=flat-square)](https://scrutinizer-ci.com/g/divineniiquaye/rade-di)
[![Sponsor development of this project](https://img.shields.io/badge/sponsor%20this%20package-%E2%9D%A4-ff69b4.svg?style=flat-square)](https://biurad.com/sponsor)

**divineniiquaye/rade-di** is a HIGH performance smart tool for performing simple to complex dependency injection in your application for [PHP] 7.4+ created by [Divine Niiquaye][@divineniiquaye] referenced to [Nette DI][nette-di] and [Pimple]. This library provides an advance way of resolving services for best performance to your application.

## 📦 Installation & Basic Usage

This project requires [PHP] 7.4 or higher. The recommended way to install, is via [Composer]. Simply run:

```bash
$ composer require divineniiquaye/rade-di
```

Creating a container is a matter of creating a ``Container`` instance:

```php
use Rade\DI\Container;

$container = new Container();
```

For registering services into container, a service must be a real valid PHP object type. Container implements both PSR-11 `ContainerInterface` and `ArrayAccess`, so here's an example to demonstrate:

> Using Container without `ArrayAccess`

```php
// Should service be autowired or not ...
$autowire = true;

// define some services
$container->set('session_storage', new SessionStorage('SESSION_ID'), $autowire);

$container->set(
    'session', // The unique service id identifier
    static fn(): Session => new Session($container['session_storage']),
    $autowire
);
// or
$container->set('session', $container->resolveClass(Session::class), $autowire);
// or further
$container->set('session', $container->lazy(Session::class), $autowire);
```

> Using Container with `ArrayAccess`

```php
// define some services
$container['session_storage'] = new SessionStorage('SESSION_ID');

$container['session'] = fn(): Session => new Session($container['session_storage']);
// or
$container['session'] = $container->lazy(Session::class);
// or
$container['session'] = $container->resolveClass(Session::class);
// or further
$container['session'] = new Session($container['session_storage']);
```

Using the defined services is also very easy:

```php
// get the session object
$session = $container['session'];
// or
$session = $container->get('session');
// or using ArrayAccess
$session = $container['session'];
// or use it's service class name, parent classes or interfaces
$session = $container->get(Session::class);


// the above call is roughly equivalent to the following code:
$storage = new SessionStorage('SESSION_ID');
$session = new Session($storage);
```

Container supports reuseable service instance. This is means, a registered service which is resolved, is frozen and object's id does not change throughout your application using Rade DI.

Rade DI also supports autowiring except a return type of a callable is not define or better still if you do not want autowiring at all, use the container's **set** method. By default, registering services with `ArrayAccess` implementation are all autowired.

>To prevent registered services from being shared, use the container's **factory** method.

```php
$container['session'] = $container->definition(new Session($container['session_storage']), Definition::FACTORY);
```

With the example above, each call to `$container['session']` returns a new instance of the session.

In some cases you may want to modify a service definition after it has been defined. You can use the ``extend()`` method to define additional code to be run on your service just after it is created:

```php
$container['session_storage'] = function (Container $container) {
    return new $container['session_storage_class']($container['cookie_name']);
};

// By default container is passed unto second parameter, but can be omitted.
$container->extend('session_storage', function ($storage) {
    $storage->...();

    return $storage;
});
```

The first argument is the name of the service to extend, the second a function that gets access to the object instance and the container.

Also Rade has aliasing and tagging support for services. If you want to add a different name to a registered service, use `alias` method.

```php
$container['film'] = new Movie('S1', 'EP202');
$container->alias('movie', 'film');

// Can be access by $container['film'] or $container['movie']
```

For tagging, perhaps you are building a report aggregator that receives an array of many different `Report` interface implementations.

```php
$container['speed.report'] = new SpeedReport(...);
$container['memory.report'] = new MemoryReport(...);

$container->tag([SpeedReport::class, MemoryReport::class], ['reports']);
```

Once the services have been tagged, you may easily resolve them all via the `tagged` method:

```php
$tags = $container->tagged('reports');
$reports = [];

foreach ($tags as [$report, $attr]) {
    $reports[] = $report;
}

$container->tag([SpeedReport::class, MemoryReport::class], ['reports'])

$manager = new ReportAggregator($reports);

// For the $attr var, this is useful if you need tag to have extra values
// for tagging, eg:
$container->tag([BackupProcessor::class, MonitorProcessor::class], ['process' => true]);
$container->tag(CacheProcessor::class, ['process' => false]);

foreach ($container->tagged('process') as [$process, $enabled]) {
    if ($enabled) {
        $manager->addProcessor($process);
    }
}
```

Rade Di has service provider support, which allows the container to be extensible and reuseable. With Rade DI, your project do not need so to depend on PSR-11 container so much. Using service providers in your project, saves you alot.

```php
use Rade\DI\Container;

class FooProvider implements Rade\DI\ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(AbstractContainer $container, array $configs = []): void
    {
        // register some services and parameters
        // on $container
    }
}
```

Then, register the provider on a Container:

```php
$container->register(new FooProvider());
```

Service providers support [Symfony's config component][symfony-config] for writing configuration for service definitions found in a provider. Implement the service provider class to `Symfony\Component\Config\Definition\ConfigurationInterface`.

Writing configurations for a service provider by default, the service provider's class name, becomes the key pointing to the require config data. Want to use a custom key name, set add a static **getId** method returning your custom key name.

>Using [Symfony's config component][symfony-config] + `Rade\DI\ContainerBuilder` class is highly recommended.

```bash
$ composer require symfony/config
```

Also the `Rade\DI\ServiceLocator` class is intended of setting predefined services while instantiating them only when actually needed.

For service locators, Rade uses [symfony's service contracts](https://github.com/symfony/service-contracts).

It also allows you to make your services available under different naming. For instance, you may want to use an object that expects an instance of `EventDispatcherInterface` to be available under the name `event_dispatcher` while your event dispatcher has been registered under the name `dispatcher`:

```php
use Monolog\Logger;
use Rade\DI\ServiceLocator;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class MyService implements ServiceSubscriberInterface
{
    /**
     * "logger" must be an instance of Psr\Log\LoggerInterface
     * "event_dispatcher" must be an instance of Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private ?ContainerInterface $container;

    public function __construct(ServiceProviderInterface $provider = null)
    {
        $this->container = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return ['logger', 'event_dispatcher' => 'dispatcher'];
    }
}

$container['logger'] = new Monolog\Logger();
$container['dispatcher'] = new EventDispatcher();

$container['service'] = $container->lazy(MyService::class);
```

## 📓 Documentation

For in-depth documentation before using this library. Full documentation on advanced usage, configuration, and customization can be found at [docs.divinenii.com][docs].

## ⏫ Upgrading

Information on how to upgrade to newer versions of this library can be found in the [UPGRADE].

## 🏷️ Changelog

[SemVer](http://semver.org/) is followed closely. Minor and patch releases should not introduce breaking changes to the codebase; See [CHANGELOG] for more information on what has changed recently.

Any classes or methods marked `@internal` are not intended for use outside of this library and are subject to breaking changes at any time, so please avoid using them.

## 🛠️ Maintenance & Support

When a new **major** version is released (`1.0`, `2.0`, etc), the previous one (`0.19.x`) will receive bug fixes for _at least_ 3 months and security updates for 6 months after that new release comes out.

(This policy may change in the future and exceptions may be made on a case-by-case basis.)

**Professional support, including notification of new releases and security updates, is available at [Biurad Commits][commit].**

## 👷‍♀️ Contributing

To report a security vulnerability, please use the [Biurad Security](https://security.biurad.com). We will coordinate the fix and eventually commit the solution in this project.

Contributions to this library are **welcome**, especially ones that:

- Improve usability or flexibility without compromising our ability to adhere to [PSR-12] coding standard.
- Optimize performance and add new features
- Fix issues with adhering to [PSR-11] support and backward compatibility.
-

Please see [CONTRIBUTING] for additional details.

## 🧪 Testing

```bash
$ ./vendor/bin/phpunit
```

This will tests divineniiquaye/rade-di will run against PHP 7.4 version or higher.

## 👥 Credits & Acknowledgements

- [Divine Niiquaye Ibok][@divineniiquaye]
- [All Contributors][]

## 🙌 Sponsors

Are you interested in sponsoring development of this project? Reach out and support us on [Patreon](https://www.patreon.com/biurad) or see <https://biurad.com/sponsor> for a list of ways to contribute.

## 📄 License

**divineniiquaye/rade-di** is licensed under the BSD-3 license. See the [`LICENSE`](LICENSE) file for more details.

## 🏛️ Governance

This project is primarily maintained by [Divine Niiquaye Ibok][@divineniiquaye]. Members of the [Biurad Lap][] Leadership Team may occasionally assist with some of these duties.

## 🗺️ Who Uses It?

You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us an [email] or [message] mentioning this library. We publish all received request's at <https://patreons.biurad.com>.

Check out the other cool things people are doing with `divineniiquaye/rade-di`: <https://packagist.org/packages/divineniiquaye/rade-di/dependents>

[PHP]: https://php.net
[Composer]: https://getcomposer.org
[@divineniiquaye]: https://github.com/divineniiquaye
[docs]: https://docs.divinenii.com/rade-di
[commit]: https://commits.biurad.com/php-starter.git
[UPGRADE]: UPGRADE-1.x.md
[CHANGELOG]: CHANGELOG-0.x.md
[CONTRIBUTING]: ./.github/CONTRIBUTING.md
[All Contributors]: https://github.com/divineniiquaye/rade-di/contributors
[Biurad Lap]: https://team.biurad.com
[email]: support@biurad.com
[message]: https://projects.biurad.com/message
[nette-di]: https://github.com/nette/di
[symfony-config]: https://github.com/symfony/config
[Pimple]: https://github.com/silexphp/pimple
[PSR-11]: http://www.php-fig.org/psr/psr-11/
[PSR-12]: http://www.php-fig.org/psr/psr-12/
