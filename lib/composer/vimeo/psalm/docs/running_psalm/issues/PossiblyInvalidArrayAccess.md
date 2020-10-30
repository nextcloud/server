# PossiblyInvalidArrayAccess

Emitted when attempting to access an array offset on a value that may not be an array

```php
<?php

$arr = rand(0, 1) ? 5 : [4, 3, 2, 1];
echo $arr[0];
```
