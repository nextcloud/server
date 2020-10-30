# MissingTemplateParam

Emitted when using the `@extends`/`@implements` annotation to extend a class without
extending all its template params.

```php
<?php

/**
 * @template-implements ArrayAccess<int>
 */
class SomeIterator implements ArrayAccess
{
    public function offsetSet($offset, $value) {
    }

    public function offsetExists($offset) {
        return false;
    }

    public function offsetUnset($offset) {
    }

    public function offsetGet($offset) {
        return null;
    }
}
```
