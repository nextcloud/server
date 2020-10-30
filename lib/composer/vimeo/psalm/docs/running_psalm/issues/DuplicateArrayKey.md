# DuplicateArrayKey

Emitted when an array has a key more than once

```php
<?php

$arr = [
    'a' => 'one',
    'b' => 'two',
    'c' => 'this text will be overwritten by the next line',
    'c' => 'three',
];
```

## How to fix

Remove the offending duplicates:

```php
<?php

$arr = [
    'a' => 'one',
    'b' => 'two',
    'c' => 'three',
];
```

The first matching `'c'` key was removed to prevent a change in behaviour (any new duplicate keys overwrite the values of previous ones).
