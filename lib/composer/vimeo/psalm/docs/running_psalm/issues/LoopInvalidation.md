# LoopInvalidation

Emitted when logic inside a loop invalidates one of the conditionals of the loop

```php
<?php

for ($i = 0; $i < 10; $i++) {
    $i = 5;
}
```
