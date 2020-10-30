# UnusedPsalmSuppress

Emitted when `--find-unused-psalm-suppress` is turned on and Psalm cannot find any uses of a given `@psalm-suppress` annotation

```php
<?php

/** @psalm-suppress InvalidArgument */
echo strlen("hello");
```
