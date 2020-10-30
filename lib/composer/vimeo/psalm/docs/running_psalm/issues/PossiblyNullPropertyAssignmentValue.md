# PossiblyNullPropertyAssignmentValue

Emitted when trying to assign a value that may be null to a property that only takes non-null values.

```php
<?php

class A {
    /** @var string */
    public $foo = "bar";
}

function assignToA(?string $s) {
    $a = new A();
    $a->foo = $s;
}
```
