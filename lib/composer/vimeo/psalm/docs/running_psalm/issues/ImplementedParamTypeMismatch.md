# ImplementedParamTypeMismatch

Emitted when a class that inherits another, or implements an interface, has a docblock param type that's entirely different to the parent.

```php
<?php

class D {
    /** @param string $a */
    public function foo($a): void {}
}

class E extends D {
    /** @param int $a */
    public function foo($a): void {}
}
```

## How to fix

Make sure to respect the [Liskov substitution principle](https://en.wikipedia.org/wiki/Liskov_substitution_principle) – any method that overrides a parent method must accept all the same arguments as its parent method.

```php
<?php

class D {
    /** @param string $a */
    public function foo($a): void {}
}

class E extends D {
    /** @param string|int $a */
    public function foo($a): void {}
}
```
