# ParamNameMismatch

Emitted when method overrides a parent method but renames a param.

```php
<?php

class A {
    public function foo(string $str, bool $b = false) : void {}
}

class AChild extends A {
    public function foo(string $string, bool $b = false) : void {}
}
```

## Why is this bad?

PHP 8 introduces [named parameters](https://wiki.php.net/rfc/named_params) which allow developers to call methods with explicitly-named parameters;

```php
<?php

function callFoo(A $a) {
    $a->foo(str: "hello");
}
```

In the first example passing `new AChild()` to `callFoo()` results in a fatal error, as AChild's definition of the method `foo()` doesn't have a parameter named `$str`.

## How to fix

You can change the child method param name to match:

```php
<?php

class A {
    public function foo(string $str, bool $b = false) : void {}
}

class AChild extends A {
    public function foo(string $str, bool $b = false) : void {}
}
```

This fix [can be applied automatically by Psalter](https://psalm.dev/docs/manipulating_code/fixing/#paramnamemismatch).

## Workarounds

### @no-named-arguments

Alternatively you can ignore this issue by adding a `@no-named-arguments` annotation to the parent method:

```php
<?php

class A {
    /** @no-named-arguments */
    public function foo(string $str, bool $b = false) : void {}
}

class AChild extends A {
    public function foo(string $string, bool $b = false) : void {}
}
```

Any method with this annotation will be prevented (by Psalm) from being called with named parameters, so the original issue does not matter.

### Config allowNamedArgumentCalls="false"

This prevents any use of named params in your codebase. Ideal for self-contained projects, but less ideal for libraries.

It means the original code above will not emit any errors as long as the class `A` is defined in a directory that Psalm can scan.

### Config allowInternalNamedArgumentCalls="false"

For library authors Psalm supports a more nuanced flag that tells Psalm to prohibit any named parameter calls on `@internal` classes or methods.

With that config value, this is now allowed:

```php
<?php

/**
 * @internal
 */
class A {
    public function foo(string $str, bool $b = false) : void {}
}

class AChild extends A {
    public function foo(string $string, bool $b = false) : void {}
}
```
