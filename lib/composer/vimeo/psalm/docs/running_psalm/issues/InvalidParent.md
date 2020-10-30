# InvalidParent

Emitted when a function return type is `parent`, but there's no parent class
```php
<?php

class Foo {
    public function f(): parent {}
}
```
