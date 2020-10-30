# DeprecatedMethod

Emitted when calling a deprecated method on a given class:

```php
<?php

class A {
    /** @deprecated */
    public function foo() : void {}
}
(new A())->foo();
```

## Why this is bad

The `@deprecated` tag is normally indicative of code that will stop working in the near future.

## How to fix

Don’t use the deprecated method.
