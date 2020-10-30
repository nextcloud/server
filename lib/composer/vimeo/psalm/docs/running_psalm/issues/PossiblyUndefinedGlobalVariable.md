# PossiblyUndefinedGlobalVariable

Emitted when trying to access a variable in the global scope that may not be defined

```php
<?php

if (rand(0, 1)) {
  $a = 5;
}
echo $a;
```
