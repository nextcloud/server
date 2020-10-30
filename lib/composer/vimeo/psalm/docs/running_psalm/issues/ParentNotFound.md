# ParentNotFound

Emitted when using `parent::` in a class without a parent class.

```php
<?php

class A {
  public function foo() : void {
    parent::foo();
  }
}
```
