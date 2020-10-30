# UnsafeInstantiation

Emitted when an attempt is made to instantiate a class without a constructor that's final:

```php
<?php

class A {
    public function getInstance() : self
    {
        return new static();
    }
}
```

## What’s wrong here?

The problem comes when extending the class:

```php
<?php

class A {
    public function getInstance() : self
    {
        return new static();
    }
}

class AChild extends A {
    public function __construct(string $some_required_param) {}
}

AChild::getInstance(); // fatal error
```

## How to fix

You have two options – you can make the constructor final:

```php
<?php

class A {
    final public function __construct() {}

    public function getInstance() : self
    {
        return new static();
    }
}
```

Or you can add a `@psalm-consistent-constructor` annotation which ensures that any constructor in a child class has the same signature as the parent constructor:

```php
<?php

/**
 * @psalm-consistent-constructor
 */
class A {
    public function getInstance() : self
    {
        return new static();
    }
}

class AChild extends A {
    public function __construct() {
        // this is fine
    }
}

class BadAChild extends A {
    public function __construct(string $s) {
        // this is reported as a violation
    }
}
```
