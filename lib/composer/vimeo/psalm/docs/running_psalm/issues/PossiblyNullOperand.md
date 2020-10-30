# PossiblyNullOperand

Emitted when using a possibly `null` value as part of an operation (e.g. `+`, `.`, `^` etc.)

```php
<?php

function foo(?int $a) : void {
    echo $a + 5;
}
```
