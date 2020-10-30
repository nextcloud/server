# NullArgument

Emitted when calling a function with a null value argument when the function does not expect it

```php
<?php

function foo(string $s) : void {}
foo(null);
```
