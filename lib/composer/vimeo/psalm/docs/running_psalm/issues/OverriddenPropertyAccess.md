# OverriddenPropertyAccess

Emitted when a property is less accessible than the same-named property in its parent class

```php
<?php

class A {
    /** @var string|null */
    public $foo;
}
class B extends A {
    /** @var string|null */
    protected $foo;
}
```
