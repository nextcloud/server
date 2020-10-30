# MissingDependency

Emitted when referencing a class that does not exist

```php
<?php

/**
 * @psalm-suppress UndefinedClass
 */
class A extends B {}

$a = new A();
```
