# NoInterfaceProperties

Emitted when trying to fetch a property on an interface as interfaces, by definition, do not have definitions for properties.

```php
<?php

interface I {}
class A implements I {
    /** @var ?string */
    public $foo;
}
function bar(I $i) : void {
    if ($i->foo) {}
}
```
