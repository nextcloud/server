# PossiblyFalseReference

Emitted when making a method call on a value than might be `false`

```php
<?php

class A {
    public function bar() : void {}
}

/** @return A|false */
function foo() {
    return rand(0, 1) ? new A : false;
}

foo()->bar();
```
