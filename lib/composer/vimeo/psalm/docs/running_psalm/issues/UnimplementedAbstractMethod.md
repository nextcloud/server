# UnimplementedAbstractMethod

Emitted when a class extends another, but does not implement all of its abstract methods

```php
<?php

abstract class A {
    abstract public function foo() : void;
}
class B extends A {}
```
