# PossiblyFalseOperand

Emitted when using a possibly `false` value as part of an operation (e.g. `+`, `.`, `^` etc).

```php
<?php

function echoCommaPosition(string $str) : void {
    echo 'The comma is located at ' . strpos($str, ','); 
}
```

## How to fix

You can detect the `false` value with some extra logic:

```php
<?php

function echoCommaPosition(string $str) : void {
    $pos = strpos($str, ',');

    if ($pos === false) {
        echo 'There is no comma in the string';
    }

    echo 'The comma is located at ' . $pos; 
}
```

Alternatively you can just use a ternary to suppress this issue:

```php
<?php

function echoCommaPosition(string $str) : void {
    echo 'The comma is located at ' . (strpos($str, ',') ?: ''); 
}
```
