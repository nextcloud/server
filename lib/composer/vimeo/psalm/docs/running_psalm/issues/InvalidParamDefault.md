# InvalidParamDefault

Emitted when a function parameter default clashes with the type Psalm expects the param to be

```php
<?php

function foo(int $i = false) : void {}
```
