# MissingThrowsDocblock

Enabled when the `checkForThrowsDocblock` configuration option is enabled.

Emitted when a function throws (or fails to handle) an exception and does not have a `@throws` annotation.

```php
<?php

function foo(int $x, int $y) : int {
    if ($y === 0) {
        throw new \InvalidArgumentException('Cannot divide by zero');
    }

    return intdiv($x, $y);
}
```
