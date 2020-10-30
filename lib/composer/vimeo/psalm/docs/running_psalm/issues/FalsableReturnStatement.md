# FalsableReturnStatement

Emitted if a return statement contains a false value, but the function return type does not allow false

```php
<?php

function getCommaPosition(string $a) : int {
    return strpos($a, ',');
}
```

## How to fix

You can add a specific check for false:

```php
<?php

function getCommaPosition(string $a) : int {
    $pos = return strpos($a, ',');

    if ($pos === false) {
        return -1;
    }

    return $pos;
}
```

Alternatively you may chose to throw an exception:

```php
<?php

function getCommaPosition(string $a) : int {
    $pos = return strpos($a, ',');

    if ($pos === false) {
        throw new Exception('This is unexpected');
    }

    return $pos;
}
```
