# NullArrayOffset

Emitted when trying to access an array offset with `null`

```php
<?php

$arr = ['' => 5, 'foo' => 1];
echo $arr[null];
```
