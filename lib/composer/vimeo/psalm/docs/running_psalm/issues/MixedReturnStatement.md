# MixedReturnStatement

Emitted when Psalm cannot determine the type of a given return statement

```php
<?php

function foo() : int {
    return $_GET['foo']; // emitted here
}
```
