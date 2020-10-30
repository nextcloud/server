# RedundantIdentityWithTrue

Emitted when comparing a known boolean with true and the `strictBinaryOperands` flag is set to true.

```php
<?php

function returnsABool(): bool {
    return rand(1, 2) === 1;
}

if (returnsABool() === true) {
    echo "hi!";
}
```
