# RawObjectIteration

Emitted when iterating over an object’s properties. This issue exists because it may be undesired behaviour (e.g. you may have meant to iterate over an array)

```php
<?php

class A {
    /** @var string|null */
    public $foo;

    /** @var string|null */
    public $bar;
}

function takesA(A $a) {
    foreach ($a as $property) {}
}
```
