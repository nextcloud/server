# UndefinedPropertyAssignment

Emitted when assigning a property on an object that does not have that property defined

```php
<?php

class A {}
$a = new A();
$a->foo = "bar";
```
