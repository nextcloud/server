# PropertyNotSetInConstructor

Emitted when a non-null property without a default value is declared but not set in the class’s constructor

```php
<?php

class A {
    /** @var string */
    public $foo;

    public function __construct() {}
}
```
