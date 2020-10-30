# MixedArrayTypeCoercion

Emitted when trying to access an array with a less specific offset than is expected

```php
<?php

/**
 * @param array<array-key, int> $a
 * @param array<int, string> $b
 */
function foo(array $a, array $b) : void {
    foreach ($a as $j => $k) {
        echo $b[$j];
    }
}
```
