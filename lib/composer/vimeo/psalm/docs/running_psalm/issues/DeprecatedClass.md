# DeprecatedClass

Emitted when referring to a deprecated class:

```php
<?php

/** @deprecated */
class A {}
new A();
```

## Why this is bad

The `@deprecated` tag is normally indicative of code that will stop working in the near future.

## How to fix

Don’t use the deprecated class.
