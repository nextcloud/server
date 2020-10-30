# FalseOperand

Emitted when using `false` as part of an operation (e.g. `+`, `.`, `^` etc.)

```php
<?php

echo 5 . false; 
```

## Why this is bad

`false` does not make sense in these operations
