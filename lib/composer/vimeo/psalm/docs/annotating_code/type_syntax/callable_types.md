# Callable types

Psalm supports a special format for `callable`s of the form. It can also be used for annotating `Closure`.

```
callable(Type1, OptionalType2=, SpreadType3...):ReturnType
```

Adding `=` after the type implies it is optional, and suffixing with `...` implies the use of the spread operator.

Using this annotation you can specify that a given function return a `Closure` e.g.

```php
<?php
/**
 * @return Closure(bool):int
 */
function delayedAdd(int $x, int $y) : Closure {
  return function(bool $debug) use ($x, $y) {
    if ($debug) echo "got here" . PHP_EOL;
    return $x + $y;
  };
}

$adder = delayedAdd(3, 4);
echo $adder(true);
```
