# InvalidReturnType

Emitted when a function’s signature return type is incorrect (often emitted with `InvalidReturnStatement`)

```php
<?php

function foo() : int {
    if (rand(0, 1)) {
        return "hello";
    }

    return 5;
}
```
