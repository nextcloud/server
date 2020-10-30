# PossiblyUndefinedMethod

Emitted when trying to access a method that may not be defined on the object

```php
<?php

class A {
    public function bar() : void {}
}
class B {}

$a = rand(0, 1) ? new A : new B;
$a->bar();
```
