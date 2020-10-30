# OverriddenMethodAccess

Emitted when a method is less accessible than its parent

```php
<?php

class A {
    public function foo() : void {}
}
class B extends A {
    protected function foo() : void {}
}
```
