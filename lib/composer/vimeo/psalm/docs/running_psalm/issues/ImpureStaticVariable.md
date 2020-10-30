# ImpureStaticVariable

Emitted when attempting to use a static variable from a function or method marked as pure

```php
<?php

/** @psalm-pure */
function addCumulative(int $left) : int {
    /** @var int */
    static $i = 0;
    $i += $left;
    return $left;
}
```
