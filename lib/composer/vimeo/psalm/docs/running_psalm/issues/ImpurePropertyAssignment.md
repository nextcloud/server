# ImpurePropertyAssignment

Emitted when updating a property value from a function or method marked as pure.

```php
<?php

class A {
    public int $a = 5;
}

/** @psalm-pure */
function foo(int $i, A $a) : int {
    $a->a = $i;

    return $i;
}
```
