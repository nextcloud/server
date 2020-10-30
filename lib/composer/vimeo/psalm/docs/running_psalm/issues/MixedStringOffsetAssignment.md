# MixedStringOffsetAssignment

Emitted when assigning a value on a string using a value for which Psalm cannot infer a type

```php
<?php

"hello"[0] = $_GET['foo'];
```
