# MixedPropertyTypeCoercion

Emitted when Psalm cannot be sure that part of an array/iterable argument's type constraints can be fulfilled

```php
<?php

class A {
    /** @var string[] */
    public $takesStringArray = [];
}

function foo(A $a, array $arr) : void {
    $a->takesStringArray = $arr;
}
```
