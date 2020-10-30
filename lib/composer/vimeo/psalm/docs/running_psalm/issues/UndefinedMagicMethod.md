# UndefinedMagicMethod

Emitted when calling a magic method that does not exist

```php
<?php

/**
 * @method bar():string
 */
class A {
    public function __call(string $name, array $args) {
        return "cool";
    }
}
(new A)->foo();
```
