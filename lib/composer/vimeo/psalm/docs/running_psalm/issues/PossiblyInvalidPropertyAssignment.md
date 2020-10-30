# PossiblyInvalidPropertyAssignment

Emitted when trying to assign a property on a value that may not be an object or may be an object that does not have the desired property.

```php
<?php

class A {
    /** @var ?string */
    public $bar;
}

/** @return A|int */
function foo() {
    return rand(0, 1) ? new A : 5;
}

$a = foo();
$a->bar = "5";
```
