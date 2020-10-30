# UnhandledMatchCondition

Emitted when a match expression does not handle one or more options.

```php
<?php

function matchOne(): string {
    $foo = rand(0, 1) ? "foo" : "bar";

    return match ($foo) {
        'foo' => 'foo',
    };
}
```

## Why this is bad

The above code will fail 50% of the time with an `UnhandledMatchError` error.
