# PossiblyNullPropertyFetch

Emitted when trying to fetch a property on a possibly null object

```php
<?php

class A {
    /** @var ?string */
    public $foo;
}
function foo(?A $a) : void {
    echo $a->foo;
}
```
