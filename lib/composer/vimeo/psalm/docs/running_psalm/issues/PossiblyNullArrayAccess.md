# PossiblyNullArrayAccess

Emitted when trying to access an array offset on a possibly null value

```php
<?php

function foo(?array $a) : void {
    echo $a[0];
}
```
