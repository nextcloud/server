# UnresolvableInclude

Emitted when Psalm cannot figure out what specific file is being included/required by PHP.

```php
<?php

function requireFile(string $s) : void {
    require_once($s);
}
```
