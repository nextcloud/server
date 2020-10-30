# PossiblyInvalidOperand

Emitted when using a possibly invalid value as part of an operation (e.g. `+`, `.`, `^` etc.

```php
<?php

function foo() : void {
    $b = rand(0, 1) ? [] : 4;
    echo $b + 5;
}
```
