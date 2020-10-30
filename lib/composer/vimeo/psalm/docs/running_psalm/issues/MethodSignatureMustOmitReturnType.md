# MethodSignatureMustOmitReturnType

Emitted when a `__clone`, `__construct`, or `__destruct` method is defined with a return type.

```php
<?php

class A {
    public function __clone() : void {}
}
```
