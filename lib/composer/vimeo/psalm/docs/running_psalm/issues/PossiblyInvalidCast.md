# PossiblyInvalidCast

Emitted when attempting to cast a value that may not be castable

```php
<?php

class A {}
class B {
    public function __toString() {
        return 'hello';
    }
}
$c = (string) (rand(0, 1) ? new A() : new B());
```
