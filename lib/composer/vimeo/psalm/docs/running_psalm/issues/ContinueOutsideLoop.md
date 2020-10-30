# ContinueOutsideLoop

Emitted when encountering a `continue` statement outside a loop context.

```php
<?php

$a = 5;
continue;
```

## Why this is bad

The code won't compile in PHP 5.6 and above.
