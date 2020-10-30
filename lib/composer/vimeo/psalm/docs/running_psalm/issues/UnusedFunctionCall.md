# UnusedFunctionCall

Emitted when `--find-dead-code` is turned on and Psalm finds a function call whose return value is not used anywhere

```php
<?php

$a = strlen("hello");
strlen("goodbye"); // unused
echo $a;
```
