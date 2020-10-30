# PossiblyUndefinedVariable

Emitted when trying to access a variable in function scope that may not be defined

```php
<?php

function foo() : void {
    if (rand(0, 1)) {
        $a = 5;
    }
    echo $a;
}
```
