# DeprecatedFunction

Emitted when calling a deprecated function:

```php
<?php

/** @deprecated */
function foo() : void {}
foo();
```

## Why this is bad

The `@deprecated` tag is normally indicative of code that will stop working in the near future.

## How to fix

Don’t use the deprecated function.
