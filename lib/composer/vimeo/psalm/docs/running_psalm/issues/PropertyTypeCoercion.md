# PropertyTypeCoercion

Emitted when setting a property with an value which has a less specific type than the property expects

```php
<?php

class A {}
class B extends A {}

function takesA(C $c, A $a) : void {
    $c->b = $a;
}

class C {
    /** @var ?B */
    public $b;
}
```
