# PossiblyInvalidMethodCall

Emitted when trying to call a method on a value that may not be an object

```php
<?php

class A {
    public function bar() : void {}
}

/** @return A|int */
function foo() {
    return rand(0, 1) ? new A : 5;
}

foo()->bar();
```
