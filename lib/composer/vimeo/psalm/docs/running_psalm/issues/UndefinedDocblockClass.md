# UndefinedDocblockClass

Emitted when referencing a class that does not exist from a docblock

```php
<?php

/**
 * @param DoesNotExist $a
 */
function foo($a) : void {}
```
