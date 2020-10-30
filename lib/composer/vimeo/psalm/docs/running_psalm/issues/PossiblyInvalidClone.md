# PossiblyInvalidClone

Emitted when trying to clone a value that's possibly not cloneable

```php
<?php

class A {}

/**
 * @param A|string $a
 */
function foo($a) {
    return clone $a;
}
```
