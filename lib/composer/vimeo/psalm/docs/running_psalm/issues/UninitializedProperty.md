# UninitializedProperty

Emitted when a property is used in a constructor before it is initialized

```php
<?php

class A {
    /** @var string */
    public $foo;

    public function __construct() {
        echo strlen($this->foo);
        $this->foo = "foo";
    }
}
```
