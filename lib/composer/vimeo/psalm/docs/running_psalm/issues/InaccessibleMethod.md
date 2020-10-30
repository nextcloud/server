# InaccessibleMethod

Emitted when attempting to access a protected/private method from outside its available scope

```php
<?php

class A {
    protected function foo() : void {}
}
echo (new A)->foo();
```
