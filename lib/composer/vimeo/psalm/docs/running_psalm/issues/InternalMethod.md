# InternalMethod

Emitted when attempting to access a method marked as internal an unrelated namespace or class, or attempting
to access a method marked as psalm-internal to a different namespace.

```php
<?php

namespace A {
    class Foo {
        /**
         * @internal
         */
        public static function barBar(): void {
        }
    }
}
namespace B {
    class Bat {
        public function batBat(): void {
            \A\Foo::barBar();
        }
    }
}
```
