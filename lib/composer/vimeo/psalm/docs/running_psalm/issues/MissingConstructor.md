# MissingConstructor

Emitted when non-null properties without default values are defined in a class without a `__construct` method

```php
<?php

class A {
    /** @var string */
    public $foo;
}
```
