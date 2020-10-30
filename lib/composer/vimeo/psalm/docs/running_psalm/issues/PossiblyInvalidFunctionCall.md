# PossiblyInvalidFunctionCall

Emitted when trying to call a function on a value that may not be callable

```php
<?php

$a = rand(0, 1) ? 5 : function() : int { return 5; };
$b = $a();
```
