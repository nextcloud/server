# PossiblyInvalidArgument

Emitted when

```php
<?php

/** @return int|stdClass */
function foo() {
    return rand(0, 1) ? 5 : new stdClass;
}
function bar(int $i) : void {}
bar(foo());
```
