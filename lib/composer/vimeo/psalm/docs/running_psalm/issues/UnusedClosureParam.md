# UnusedClosureParam

Emitted when `--find-dead-code` is turned on and Psalm cannot find any uses of a particular parameter in a closure.

```php
<?php

$a = function (int $a, int $b) : int {
    return $a + 4;
};

/**
 * @param callable(int,int):int $c
 */
function foo(callable $c) : int {
    return $c(2, 4);
}
```

Can be suppressed by prefixing the parameter name with an underscore:

```php
$f = function (int $_a, int $b) : int {
    return $b + 4;
};
```
