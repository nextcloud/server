# PossiblyUndefinedIntArrayOffset

Emitted when the config flag `ensureArrayIntOffsetsExist` is set to `true` and an integer-keyed offset is not checked for existence

```php
<?php

/**
 * @param array<int, string> $arr
 */
function foo(array $arr) : void {
    echo $arr[0];
}
```
