Guzzle Iterator
===============

Provides useful Iterators and Iterator decorators

- ChunkedIterator: Pulls out chunks from an inner iterator and yields the chunks as arrays
- FilterIterator: Used when PHP 5.4's CallbackFilterIterator is not available
- MapIterator: Maps values before yielding
- MethodProxyIterator: Proxies missing method calls to the innermost iterator

### Installing via Composer

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php

# Add Guzzle as a dependency
php composer.phar require guzzle/iterator:~3.0
```

After installing, you need to require Composer's autoloader:

```php
require 'vendor/autoload.php';
```
