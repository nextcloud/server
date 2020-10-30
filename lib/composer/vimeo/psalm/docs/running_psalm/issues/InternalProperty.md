# InternalProperty

Emitted when attempting to access a property marked as internal from an unrelated namespace or class, or attempting
to access a property marked as psalm-internal to a different namespace.

```php
<?php

namespace A {
    class Foo {
        /**
         * @internal
         * @var ?int
         */
        public $foo;
    }
}

namespace B {
    class Bat {
        public function batBat() : void {
            echo (new \A\Foo)->foo;
        }
    }
}
```
