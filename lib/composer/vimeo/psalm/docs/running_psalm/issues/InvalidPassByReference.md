# InvalidPassByReference

Emitted when passing a non-variable to a function that expects a by-ref variable

```php
<?php

function foo(array &$arr) : void {}
foo([0, 1, 2]);
```
