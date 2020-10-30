# PossiblyNullIterator

Emitted when trying to iterate over a value that may be null

```php
<?php

function foo(?array $arr) : void {
    foreach ($arr as $a) {}
}
```
