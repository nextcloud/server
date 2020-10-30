# UndefinedThisPropertyAssignment

Emitted when assigning a property on an object in one of that object’s methods when no such property exists

```php
<?php

class A {
    function foo() {
        $this->foo = "bar";
    }
}
```
