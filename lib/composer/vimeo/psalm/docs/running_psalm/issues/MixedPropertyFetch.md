# MixedPropertyFetch

Emitted when retrieving a property on a value for which Psalm cannot infer a type

```php
<?php

/** @param mixed $a */
function foo($a) : void {
    echo $a->foo;
}
```
