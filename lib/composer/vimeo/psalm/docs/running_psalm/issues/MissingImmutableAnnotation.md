# MissingImmutableAnnotation

Emitted when a class inheriting from an immutable interface or class does not also have a `@psalm-immutable` declaration

```php
<?php

/** @psalm-immutable */
interface SomethingImmutable {
    public function someInteger() : int;
}

class MutableImplementation implements SomethingImmutable {
    private int $counter = 0;
    public function someInteger() : int {
        return ++$this->counter;
    }
}
```
