# InvalidTemplateParam

Emitted when using the `@extends`/`@implements` annotation to extend a class that has a template type constraint, where that extended value does not satisfy the parent class/interface's constraints.

```php
<?php

/**
 * @template T as object
 */
class Base {}

/** @template-extends Base<int> */
class SpecializedByInheritance extends Base {}
```
