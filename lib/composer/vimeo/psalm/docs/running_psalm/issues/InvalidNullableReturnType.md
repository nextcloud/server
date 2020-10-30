# InvalidNullableReturnType

Emitted when a function can return a nullable value, but its given return type says otherwise

```php
<?php

function foo() : string {
    if (rand(0, 1)) {
        return "foo";
    }

    return null;
}
```
