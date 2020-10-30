# InvalidDocblockParamName

Emitted when a docblock param name does not match up with a named param in the function, if the param does not have a type or its type is `array`.

```php
<?php

/**
 * @param string[] $bar
 */
function foo(array $barb): void {
    //
}
```
