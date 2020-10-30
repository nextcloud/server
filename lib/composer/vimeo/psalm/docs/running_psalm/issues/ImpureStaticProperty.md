# ImpureStaticProperty

Emitted when attempting to use a static property from a function or method marked as pure

```php
<?php

class ValueHolder {
    public static ?string $value = null;

    /**
     * @psalm-pure
     */
    public static function get(): ?string {
        return self::$value;
    }
}
```
