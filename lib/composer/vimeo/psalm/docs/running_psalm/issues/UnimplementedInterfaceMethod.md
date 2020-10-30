# UnimplementedInterfaceMethod

Emitted when a class `implements` an interface but does not implement all of its methods

```php
<?php

interface I {
    public function foo() : void;
}
class A implements I {}
```
