# RedundantCondition

Emitted when conditional is redundant given previous assertions

```php
<?php

class A {}
function foo(A $a) : ?A {
    if ($a) return $a;
    return null;
}
```
