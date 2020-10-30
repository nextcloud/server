# ConflictingReferenceConstraint

Emitted when a by-ref variable is set in two different branches of an if to different types.

```php
<?php

 class A {
    /** @var int */
    private $foo;

    public function __construct(int &$foo) {
        $this->foo = &$foo;
    }
}

class B {
    /** @var string */
    private $bar;

    public function __construct(string &$bar) {
        $this->bar = &$bar;
    }
}

if (rand(0, 1)) {
    $v = 5;
    $c = (new A($v)); // $v is constrained to an int
} else {
    $v = "hello";
    $c = (new B($v)); // $v is constrained to a string
}

$v = 8;
```

## Why this is bad

Psalm doesn't understand what the type of `$c` should be
