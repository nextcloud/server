# DuplicateClass

Emitted when a class is defined twice

```php
<?php

class A {}
class A {}
```

## Why this is bad

The above code won’t compile.

PHP does allow you to define a class conditionally:

```php
<?php

if (rand(0, 1)) {
    class A {
        public function __construct(string $s) {}
    }
} else {
    class A {
        public function __construct(object $o) {}
    }
}
```

But Psalm _really_ doesn't want you to use this pattern – it's impossible for Psalm to know (without using reflection) which class is getting used.
