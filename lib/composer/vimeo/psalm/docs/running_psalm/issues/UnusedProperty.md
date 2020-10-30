# UnusedProperty

Emitted when `--find-dead-code` is turned on and Psalm cannot find any uses of a private property

```php
<?php

class A {
    /** @var string|null */
    private $foo;

    /** @var int|null */
    private $bar;

    public function getFoo(): ?string {
        return $this->foo;
    }
}

$a = new A();
echo $a->getFoo();
```
