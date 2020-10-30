# PossibleRawObjectIteration

Emitted when possibly iterating over an object’s properties, the comparison to [RawObjectIteration](#rawobjectiteration).

```php
<?php

class A {
    /** @var string|null */
    public $foo;

    /** @var string|null */
    public $bar;
}

function takesA(A $a) {
    if (rand(0, 1)) {
        $a = [1, 2, 3];
    }

    foreach ($a as $property) {}
}
```
