# PossiblyInvalidArrayOffset

Emitted when it’s possible that the array offset is not applicable to the value you’re trying to access.

```php
<?php

$arr = rand(0, 1) ? ["a" => 5] : "hello";
echo $arr[0];
```
