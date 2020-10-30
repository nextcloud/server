# ArgumentTypeCoercion

Emitted when calling a function with an argument which has a less specific type than the function expects

```php
<?php

class A {}
class B extends A {}

function takesA(A $a) : void {
    takesB($a);
}
function takesB(B $b) : void {}
```

## How to fix

You could add a typecheck before the call to `takesB`:

```php
<?php

function takesA(A $a) : void {
    if ($a instanceof B) {
        takesB($a);
    }
}
```

Or, if you have control over the function signature of `takesA` you can change it to expect `B`:

```php
<?php

function takesA(B $a) : void {
    takesB($a);
}
```
