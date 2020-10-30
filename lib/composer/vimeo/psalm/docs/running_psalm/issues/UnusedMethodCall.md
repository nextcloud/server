# UnusedMethodCall

Emitted when `--find-dead-code` is turned on and Psalm finds a method call whose return value is not used anywhere

```php
<?php

final class A {
    private string $foo;

    public function __construct(string $foo) {
        $this->foo = $foo;
    }

    public function getFoo() : string {
        return $this->foo;
    }
}

$a = new A("hello");
$a->getFoo();
```
