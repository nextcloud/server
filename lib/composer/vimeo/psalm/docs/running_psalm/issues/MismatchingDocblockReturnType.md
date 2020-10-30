# MismatchingDocblockReturnType

Emitted when an `@return` entry in a function’s docblock does not match the function return typehint

```php
<?php

class A {}
class B {}
/**
 * @return B // emitted here
 */
function foo() : A {
    return new A();
}
```

This, however, is fine:

```php
<?php

class A {}
class B extends A {}
/**
 * @return B // emitted here
 */
function foo() : A {
    return new B();
}
```
