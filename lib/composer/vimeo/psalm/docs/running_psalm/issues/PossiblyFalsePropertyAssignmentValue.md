# PossiblyFalsePropertyAssignmentValue

Emitted when trying to assign a value that may be false to a property that only takes non-false values.

```php
<?php

class A {
    /** @var int */
    public $foo = 0;
}

function assignToA(string $s) {
    $a = new A();
    $a->foo = strpos($s, "haystack");
}
```
