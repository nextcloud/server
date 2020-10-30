# MixedAssignment

Emitted when assigning an unannotated variable to a value for which Psalm
cannot infer a type more specific than `mixed`.

```php
<?php

$a = $_GET['foo'];
```

## How to fix

The above example can be fixed in a few ways – by adding an `assert` call:

```php
<?php

$a = $_GET['foo'];
assert(is_string($a));
```

or by adding an explicit cast:

```php
<?php

$a = (string) $_GET['foo'];
```

or by adding a docblock

```php
<?php

/** @var string */
$a = $_GET['foo'];
```
