# InvalidCatch

Emitted when trying to catch a class/interface that doesn't extend `Exception` or implement `Throwable`

```php
<?php

class A {}
try {
    $worked = true;
}
catch (A $e) {}
```
