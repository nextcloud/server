# AbstractMethodCall

Emitted when an attempt is made to call an abstract static method directly

```php
<?php

abstract class Base {
    abstract static function bar() : void;
}

Base::bar();
```

## Why this is bad

It's not allowed by PHP.
