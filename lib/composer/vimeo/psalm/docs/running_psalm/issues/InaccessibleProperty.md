# InaccessibleProperty

Emitted when attempting to access a protected/private property from outside its available scope

```php
<?php

class A {
    /** @return string */
    protected $foo;
}
echo (new A)->foo;
```
