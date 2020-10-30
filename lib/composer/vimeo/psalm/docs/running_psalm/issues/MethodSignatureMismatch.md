# MethodSignatureMismatch

Emitted when a method parameter differs from a parent method parameter, or if there are fewer parameters than the parent method

```php
<?php

class A {
    public function foo(int $i) : void {}
}
class B extends A {
    public function foo(string $s) : void {}
}
```
