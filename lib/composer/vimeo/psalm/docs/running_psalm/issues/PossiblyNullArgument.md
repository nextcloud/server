# PossiblyNullArgument

Emitted when calling a function with a value that’s possibly null when the function does not expect it

```php
<?php

function foo(string $s) : void {}
foo(rand(0, 1) ? "hello" : null);
```
