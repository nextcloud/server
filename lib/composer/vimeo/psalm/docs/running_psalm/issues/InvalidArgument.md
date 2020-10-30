# InvalidArgument

Emitted when a supplied function/method argument is incompatible with the method signature or docblock one.

```php
<?php

class A {}

function foo(A $a) : void {}

/**
 * @param string $s
 */
function callFoo($s) : void {
    foo($s);
}
```

## Why it’s bad

Calling functions with incorrect values will cause a fatal error at runtime.

## How to fix

Sometimes this message can just be the result of an incorrect docblock.

You can fix by correcting the docblock, or converting to a function signature:

```php
<?php

class A {}

function foo(A $a) : void {}

function callFoo(A $a) : void {
    foo($a);
}
```
