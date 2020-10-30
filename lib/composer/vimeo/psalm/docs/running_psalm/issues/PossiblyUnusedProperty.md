# PossiblyUnusedProperty

Emitted when `--find-dead-code` is turned on and Psalm cannot find any uses of a particular public/protected property

```php
<?php

class A {
    /** @var string|null */
    public $foo;

    /** @var int|null */
    public $bar;
}

$a = new A();
echo $a->foo;
```
