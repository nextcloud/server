# InternalClass

Emitted when attempting to access a class marked as internal an unrelated namespace or class, or attempting
to access a class marked as psalm-internal to a different namespace.

```php
<?php

namespace A {
    /**
     * @internal
     */
    class Foo { }
}

namespace B {
    class Bat {
        public function batBat(): void {
            $a = new \A\Foo();
        }
    }
}
```
