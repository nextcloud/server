# UndefinedMagicPropertyAssignment

Emitted when assigning a property on an object that does not have that magic property defined

```php
<?php

/**
 * @property string $bar
 */
class A {
    /** @param mixed $value */
    public function __set(string $name, $value) {}
}
$a = new A();
$a->foo = "bar";
```
