# ReferenceConstraintViolation

Emitted when changing the type of a pass-by-reference variable

```php
<?php

function foo(string &$a) {
    $a = 5;
}
```
