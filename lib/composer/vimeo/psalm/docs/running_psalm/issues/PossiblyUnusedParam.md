# PossiblyUnusedParam

Emitted when `--find-dead-code` is turned on and Psalm cannot find any uses of a particular parameter in a public/protected method

```php
<?php

class A {
    public function foo(int $a, int $b) : int {
        return $a + 4;
    }
}

$a = new A();
$a->foo(1, 2);
```

Can be suppressed by prefixing the parameter name with an underscore:

```php
<?php
class A {
    public function foo(int $a, int $_b) : int {
        return $a + 4;
    }
}
```
