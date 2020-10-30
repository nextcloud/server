# LessSpecificReturnStatement

Emitted when a return statement is more general than the return type given for the function

```php
<?php

class A {}
class B extends A {}

function foo() : B {
    return new A(); // emitted here
}
```
