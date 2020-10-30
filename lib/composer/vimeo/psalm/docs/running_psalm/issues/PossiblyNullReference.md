# PossiblyNullReference

Emitted when trying to call a method on a possibly null value

```php
<?php

class A {
    public function bar() : void {}
}
function foo(?A $a) : void {
    $a->bar();
}
```
