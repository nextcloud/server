# InvalidTypeImport

Emitted when a type imported with `@psalm-import-type` is invalid

```php
<?php

namespace A;

class Types {}

namespace B;
use A\Types;
/** @psalm-import-type UnknownType from Types */
class C {}
```
