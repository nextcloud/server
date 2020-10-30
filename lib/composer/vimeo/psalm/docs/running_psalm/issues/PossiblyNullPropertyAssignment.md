# PossiblyNullPropertyAssignment

Emitted when trying to assign a property to a possibly null object

```php
<?php

class A {
    /** @var ?string */
    public $foo;
}
function foo(?A $a) : void {
    $a->foo = "bar";
}
```
