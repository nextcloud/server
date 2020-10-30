# PossiblyUndefinedArrayOffset

Emitted when trying to access a possibly undefined array offset

```php
<?php

if (rand(0, 1)) {
    $arr = ["a" => 1, "b" => 2];
} else {
    $arr = ["a" => 3];
}

echo $arr["b"];
```

## How to fix

You can use the null coalesce operator to provide a default value in the event the array offset does not exist:

```php
<?php

...

echo $arr["b"] ?? 0;
```

Alternatively, you can ensure that the array offset always exists:

```php
<?php

if (rand(0, 1)) {
    $arr = ["a" => 1, "b" => 2];
} else {
    $arr = ["a" => 3, "b" => 0];
}

echo $arr["b"];
```
