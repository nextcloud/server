# MissingClosureParamType

Emitted when a closure parameter has no type information associated with it

```php
<?php

$a = function($a): string {
    return "foo";
};
```
