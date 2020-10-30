# UnusedVariable

Emitted when `--find-dead-code` is turned on and Psalm cannot find any references to a variable, once instantiated

```php
<?php

function foo() : void {
    $a = 5;
    $b = 4;
    echo $b;
}
```

Can be suppressed by prefixing the variable name with an underscore:

```php
<?php

$_a = 22;
```
