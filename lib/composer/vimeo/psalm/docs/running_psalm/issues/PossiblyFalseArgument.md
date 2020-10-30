# PossiblyFalseArgument

Emitted when a function argument is possibly `false`, but the function does not expect `false`. This is distinct from a function argument is possibly `bool`, which results in `PossiblyInvalidArgument`.

```php
<?php

function foo(string $s) : void {
    $a_pos = strpos($s, "a");
    echo substr($s, $a_pos);
}
```
