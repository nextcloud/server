# PossiblyInvalidPropertyAssignmentValue

Emitted when trying to assign a possibly invalid value to a typed property.

```php
<?php

class A {
    /** @var int[] */
    public $bb = [];
}

class B {
    /** @var string[] */
    public $bb;
}

$c = rand(0, 1) ? new A : new B;
$c->bb = ["hello", "world"];
```
