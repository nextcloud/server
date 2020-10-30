# ImplicitToStringCast

Emitted when implicitly converting an object with a `__toString` method to a string

```php
<?php

class A {
    public function __toString() {
        return "foo";
    }
}

function takesString(string $s) : void {}

takesString(new A);
```

## How to fix

You can add an explicit string cast:

```php
<?php

...

takesString((string) new A);
```
