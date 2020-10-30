# MixedArrayAssignment

Emitted when trying to assign a value to an array offset on a value whose type Psalm cannot determine

```php
<?php

$_GET['foo'][0] = "5";
```
