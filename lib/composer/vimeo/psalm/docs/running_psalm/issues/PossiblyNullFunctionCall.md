# PossiblyNullFunctionCall

Emitted when trying to call a function on a value that may be null

```php
<?php

function foo(?callable $a) : void {
    $a();
}
```
