# PossiblyInvalidIterator

Emitted when trying to iterate over a value that may be invalid

```php
<?php

$arr = rand(0, 1) ? [1, 2, 3] : "hello";
foreach ($arr as $a) {}
```
