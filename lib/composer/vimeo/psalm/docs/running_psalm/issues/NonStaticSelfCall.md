# NonStaticSelfCall

Emitted when calling a non-static function statically

```php
<?php

class A {
    public function foo(): void {}

    public static function bar(): void {
        self::foo();
    }
}
```
