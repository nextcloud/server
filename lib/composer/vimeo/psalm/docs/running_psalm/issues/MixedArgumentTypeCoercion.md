# MixedArgumentTypeCoercion

Emitted when Psalm cannot be sure that part of an array/iterable argument's type constraints can be fulfilled

```php
<?php

function foo(array $a) : void {
    takesStringArray($a);
}

/** @param string[] $a */
function takesStringArray(array $a) : void {}
```
