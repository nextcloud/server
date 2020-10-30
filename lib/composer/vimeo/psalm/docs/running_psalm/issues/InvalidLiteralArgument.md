# InvalidLiteralArgument

Emitted when a literal argument is passed where a variable is expected, such as the first argument of `strpos`, where an explicit `$haystack` is almost always unintended.

```php
<?php

function foo(string $s) : void {
    echo strpos(".", $s);
}
```
