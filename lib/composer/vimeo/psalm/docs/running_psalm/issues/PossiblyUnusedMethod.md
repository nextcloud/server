# PossiblyUnusedMethod

Emitted when `--find-dead-code` is turned on and Psalm cannot find any calls to a given class method

```php
<?php

class A {
    public function foo() : void {}
    public function bar() : void {}
}
(new A)->foo();
```
