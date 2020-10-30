# UndefinedPropertyFetch

Emitted when getting a property on an object that does not have that property defined

```php
<?php

class A {}
$a = new A();
echo $a->foo;
```
