# ParadoxicalCondition

Emitted when a paradox is encountered in your programs logic that could not be caught by `RedundantCondition`

```php
<?php

function foo($a) : void {
    if ($a) return;
    if ($a) echo "cannot happen";
}
```
