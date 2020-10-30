# ConstructorSignatureMismatch

Emitted when a constructor parameter differs from a parent constructor parameter, or if there are fewer parameters than the parent constructor AND where the parent class has a `@psalm-consistent-constructor` annotation.

```php
<?php

/**
 * @psalm-consistent-constructor
 */
class A {
    public function __construct(int $i) {}
}
class B extends A {
    public function __construct(string $s) {}
}
```
