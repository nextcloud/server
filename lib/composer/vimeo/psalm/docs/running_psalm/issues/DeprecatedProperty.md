# DeprecatedProperty

Emitted when getting/setting a deprecated property of a given class

```php
<?php

class A {
    /**
     * @deprecated
     * @var ?string
     */
    public $foo;
}
(new A())->foo = 5;
```

## Why this is bad

The `@deprecated` tag is normally indicative of code that will stop working in the near future.

## How to fix

Don’t use the deprecated property.
