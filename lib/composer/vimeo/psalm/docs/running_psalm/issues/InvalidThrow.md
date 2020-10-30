# InvalidThrow

Emitted when trying to throw a class that doesn't extend `Exception` or implement `Throwable`

```php
<?php

class A {}
throw new A();
```
