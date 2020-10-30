# NullableReturnStatement

Emitted if a return statement contains a null value, but the function return type is not nullable

```php
<?php

function foo() : string {
    if (rand(0, 1)) {
        return "foo";
    }

    return null; // emitted here
}
```
