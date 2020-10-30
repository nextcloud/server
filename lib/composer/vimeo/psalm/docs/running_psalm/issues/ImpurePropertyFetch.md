# ImpurePropertyFetch

Emitted when fetching a property value inside a function or method marked as pure.

```php
<?php

class A {
    public int $a = 5;
}

/** @psalm-pure */
function foo(int $i, A $a) : int {
    return $i + $a->a;
}
```
