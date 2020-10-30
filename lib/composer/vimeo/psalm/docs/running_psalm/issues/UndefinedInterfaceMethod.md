# UndefinedInterfaceMethod

Emitted when calling a method that does not exist on an interface

```php
<?php

interface I {}

function foo(I $i) {
    $i->bar();
}
```
