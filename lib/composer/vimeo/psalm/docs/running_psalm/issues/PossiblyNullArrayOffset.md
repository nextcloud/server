# PossiblyNullArrayOffset

Emitted when trying to access a value on an array using a possibly null offset

```php
<?php

function foo(?int $a) : void {
    echo [1, 2, 3, 4][$a];
}
```
