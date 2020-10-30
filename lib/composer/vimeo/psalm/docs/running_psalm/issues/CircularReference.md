# CircularReference

Emitted when a class references itself as one of its parents

```php
<?php

class A extends B {}
class B extends A {}
```

## Why this is bad

The code above will not compile
