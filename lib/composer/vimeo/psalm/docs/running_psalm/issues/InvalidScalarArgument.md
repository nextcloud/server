# InvalidScalarArgument

Emitted when a scalar value is passed to a method that expected another scalar type

```php
<?php

function foo(int $i) : void {}
function bar(string $s) : void {
    if (is_numeric($s)) {
        foo($s);
    }
}
```
