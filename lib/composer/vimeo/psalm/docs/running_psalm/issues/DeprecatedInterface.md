# DeprecatedInterface

Emitted when referring to a deprecated interface

```php
<?php

/** @deprecated */
interface I {}

class A implements I {}
```

## Why this is bad

The `@deprecated` tag is normally indicative of code that will stop working in the near future.

## How to fix

Don’t use the deprecated interface.
