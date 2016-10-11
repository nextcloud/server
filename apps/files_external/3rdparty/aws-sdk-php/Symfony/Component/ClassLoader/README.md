ClassLoader Component
=====================

ClassLoader loads your project classes automatically if they follow some
standard PHP conventions.

The Universal ClassLoader is able to autoload classes that implement the PSR-0
standard or the PEAR naming convention.

First, register the autoloader:

```php
require_once __DIR__.'/src/Symfony/Component/ClassLoader/UniversalClassLoader.php';

use Symfony\Component\ClassLoader\UniversalClassLoader;

$loader = new UniversalClassLoader();
$loader->register();
```

Then, register some namespaces with the `registerNamespace()` method:

```php
$loader->registerNamespace('Symfony', __DIR__.'/src');
$loader->registerNamespace('Monolog', __DIR__.'/vendor/monolog/src');
```

The `registerNamespace()` method takes a namespace prefix and a path where to
look for the classes as arguments.

You can also register a sub-namespaces:

```php
$loader->registerNamespace('Doctrine\\Common', __DIR__.'/vendor/doctrine-common/lib');
```

The order of registration is significant and the first registered namespace
takes precedence over later registered one.

You can also register more than one path for a given namespace:

```php
$loader->registerNamespace('Symfony', array(__DIR__.'/src', __DIR__.'/symfony/src'));
```

Alternatively, you can use the `registerNamespaces()` method to register more
than one namespace at once:

```php
$loader->registerNamespaces(array(
    'Symfony'          => array(__DIR__.'/src', __DIR__.'/symfony/src'),
    'Doctrine\\Common' => __DIR__.'/vendor/doctrine-common/lib',
    'Doctrine'         => __DIR__.'/vendor/doctrine/lib',
    'Monolog'          => __DIR__.'/vendor/monolog/src',
));
```

For better performance, you can use the APC based version of the universal
class loader:

```php
require_once __DIR__.'/src/Symfony/Component/ClassLoader/UniversalClassLoader.php';
require_once __DIR__.'/src/Symfony/Component/ClassLoader/ApcUniversalClassLoader.php';

use Symfony\Component\ClassLoader\ApcUniversalClassLoader;

$loader = new ApcUniversalClassLoader('apc.prefix.');
```

Furthermore, the component provides tools to aggregate classes into a single
file, which is especially useful to improve performance on servers that do not
provide byte caches.

Resources
---------

You can run the unit tests with the following command:

    $ cd path/to/Symfony/Component/ClassLoader/
    $ composer.phar install
    $ phpunit
