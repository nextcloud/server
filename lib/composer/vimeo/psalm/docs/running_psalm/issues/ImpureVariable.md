# ImpureVariable

Emitted when referencing an impure or possibly-impure variable from a pure context.

```php
<?php

class A {
    public int $a = 5;

    /**
     * @psalm-pure
     */
    public function foo() : self {
        return $this;
    }
}
```
