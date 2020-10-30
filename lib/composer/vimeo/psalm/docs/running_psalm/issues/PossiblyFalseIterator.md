# PossiblyFalseIterator

Emitted when trying to iterate over a value that may be `false`

```php
<?php

$arr = rand(0, 1) ? [1, 2, 3] : false;
foreach ($arr as $a) {}
```
