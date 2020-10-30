# InvalidPropertyAssignmentValue

Emitted when attempting to assign a value to a property that cannot contain that type.

```php
<?php

class A {
    /** @var string|null */
    public $foo;
}
$a = new A();
$a->foo = new stdClass();
```
