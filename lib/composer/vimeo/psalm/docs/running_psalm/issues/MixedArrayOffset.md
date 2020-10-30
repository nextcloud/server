# MixedArrayOffset

Emitted when attempting to access an array offset where Psalm cannot determine the offset type

```php
<?php

echo [1, 2, 3][$_GET['foo']];
```
