# TooManyArguments

Emitted when calling a function with more arguments than the function has parameters

```php
<?php

function foo(string $a) : void {}
foo("hello", 4);
```
