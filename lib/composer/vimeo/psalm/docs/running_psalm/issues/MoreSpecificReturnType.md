# MoreSpecificReturnType

Emitted when the declared return type for a method is more specific than the inferred one (emitted in the same methods that `LessSpecificReturnStatement` is)

```php
<?php

class A {}
class B extends A {}
function foo() : B {
    /** @psalm-suppress LessSpecificReturnStatement */
    return new A();
}
```
