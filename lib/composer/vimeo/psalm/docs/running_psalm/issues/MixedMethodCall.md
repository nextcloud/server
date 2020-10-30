# MixedMethodCall

Emitted when calling a method on a value that Psalm cannot infer a type for

```php
<?php

class A {
    public function foo() : void {}
}

function callFoo(array $arr) : void {
    array_pop($arr)->foo(); // MixedMethodCall emitted here
}

callFoo(
    [new A()]
);
```

## Why this is bad

If Psalm does not know what `array_pop($arr)` is, it can't verify whether `array_pop($arr)->foo()` will work or not.

## How to fix

Make sure that to provide as much type information as possible to Psalm so that it can perform inference. For example, you could add a docblock to the `callFoo` function:

```php
<?php

class A {
    public function foo() : void {}
}

/**
 * @param  array<A> $arr
 */
function callFoo(array $arr) : void {
    array_pop($arr)->foo(); // MixedMethodCall emitted here
}

callFoo(
    [new A()]
);
```

Alternatively you could add a runtime check:

```php
<?php

class A {
    public function foo() : void {}
}

function callFoo(array $arr) : void {
    $a = array_pop($arr);
    assert($a instanceof A);
    $a->foo(); // MixedMethodCall emitted here
}

callFoo(
    [new A()]
);
```
