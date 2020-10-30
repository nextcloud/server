# DuplicateParam

Emitted when a function has a param defined twice

```php
<?php

function foo(int $b, string $b) {}
```

## Why this is bad

The above code produces a fatal error in PHP.
