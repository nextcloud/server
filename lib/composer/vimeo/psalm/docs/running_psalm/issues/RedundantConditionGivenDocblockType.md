# RedundantConditionGivenDocblockType

Emitted when conditional is redundant given information supplied in one or more docblocks.

This may be desired (e.g. when checking user input) so is distinct from RedundantCondition, which only applies to non-docblock types.

```php
<?php

/**
 * @param string $s
 *
 * @return void
 */
function foo($s) {
    if (is_string($s)) {};
}
```
