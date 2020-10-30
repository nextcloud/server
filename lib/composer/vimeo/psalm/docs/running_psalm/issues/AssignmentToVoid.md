# AssignmentToVoid

Emitted when assigning from a function that returns `void`:

```php
<?php

function foo() : void {}
$a = foo();
```

## Why this is bad

Though `void`-returning functions are treated by PHP as returning `null` (so this on its own does not lead to runtime errors), `void` is a concept more broadly in programming languages which is not designed for assignment purposes.

## How to fix

You should just be able to remove the assignment entirely:

```php
<?php

function foo() : void {}
foo();
```
