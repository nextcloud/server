# MixedPropertyAssignment

Emitted when assigning a property to a value for which Psalm cannot infer a type

```php
<?php

/** @param mixed $a */
function foo($a) : void {
    $a->foo = "bar";
}
```
