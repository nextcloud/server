# ExtensionRequirementViolation

Emitted when a using class of a trait does not extend the class specified using `@psalm-require-extends`.

```php
<?php

class A { }

/**
 * @psalm-require-extends A
 */
trait T { }

class B {
  // ExtensionRequirementViolation is emitted, as T requires
  // the using class B to extend A, which is not the case
  use T; 
}
```
