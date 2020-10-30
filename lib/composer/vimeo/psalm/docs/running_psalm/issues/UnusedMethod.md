# UnusedMethod

Emitted when `--find-dead-code` is turned on and Psalm cannot find any uses of a given private method or function

```php
<?php

class A {
    public function __construct() {
        $this->foo();
    }
    private function foo() : void {}
    private function bar() : void {}
}
$a = new A();
```
