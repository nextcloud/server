# MoreSpecificImplementedParamType

Emitted when a class implements an interface method but a param type is more specific than the interface method param type

```php
<?php

class A {}
class B extends A {
    public function bar(): void {}
}
class C extends A {
    public function bar(): void {}
}

class D {
    public function foo(A $a): void {}
}

class E extends D {
    /** @param B|C $a */
    public function foo(A $a): void {
        $a->bar();
    }
}
```
