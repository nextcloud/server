# LessSpecificReturnType

Emitted when a return type covers more possibilities than the function itself

```php
<?php

function foo() : ?int {
    return 5;
}
```
