# MixedReturnTypeCoercion

Emitted when Psalm cannot be sure that part of an array/iterable return type's constraints can be fulfilled

```php
<?php

/**
 * @return string[]
 */
function foo(array $a) : array {
    return $a;
}
```
