# TooManyTemplateParams

Emitted when using the `@extends`/`@implements` annotation to extend a class and adds too
many types.

```php
<?php

/**
 * @template-implements IteratorAggregate<int, string, int>
 */
class SomeIterator implements IteratorAggregate
{
    public function getIterator() {
        yield 5;
    }
}
```
