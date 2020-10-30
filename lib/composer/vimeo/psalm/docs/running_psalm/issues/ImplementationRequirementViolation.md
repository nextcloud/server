# ImplementationRequirementViolation

Emitted when a using class of a trait does not implement all interfaces specified using `@psalm-require-implements`.

```php
<?php

interface A { }
interface B { }

/**
 * @psalm-require-implements A
 * @psalm-require-implements B
 */
trait T { }

class C {
  // ImplementationRequirementViolation is emitted, as T requires
  // the using class C to implement A and B, which is not the case
  use T; 
}
```
