# InvalidNamedArgument

Emitted when a supplied function/method argument name is incompatible with the function/method signature.

```php
<?php

function takesArguments(string $name, int $age) : void {}

takesArguments(name: "hello", ag: 5);
```

