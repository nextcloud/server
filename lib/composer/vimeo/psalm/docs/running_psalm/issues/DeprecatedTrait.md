# DeprecatedTrait

Emitted when referring to a deprecated trait:

```php
<?php

/** @deprecated */
trait T {}
class A {
    use T;
}
```

## Why this is bad

The `@deprecated` tag is normally indicative of code that will stop working in the near future.

## How to fix

Don’t use the deprecated trait.
