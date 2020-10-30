# Supported docblock annotations

Psalm supports a wide range of docblock annotations.

## PHPDoc tags

Psalm uses the following PHPDoc tags to understand your code:

- [`@var`](https://docs.phpdoc.org/latest/references/phpdoc/tags/var.html)
  Used for specifying the types of properties and variables
- [`@return`](https://docs.phpdoc.org/latest/references/phpdoc/tags/return.html)
  Used for specifying the return types of functions, methods and closures
- [`@param`](https://docs.phpdoc.org/latest/references/phpdoc/tags/param.html)
  Used for specifying types of parameters passed to functions, methods and closures
- [`@property`](https://docs.phpdoc.org/latest/references/phpdoc/tags/property.html)
  Used to specify what properties can be accessed on an object that uses `__get` and `__set`
- [`@property-read`](https://docs.phpdoc.org/latest/references/phpdoc/tags/property-read.html)
  Used to specify what properties can be read on object that uses `__get`
- [`@property-write`](https://docs.phpdoc.org/latest/references/phpdoc/tags/property-write.html)
  Used to specify what properties can be written on object that uses `__set`
- [`@method`](https://docs.phpdoc.org/latest/references/phpdoc/tags/method.html)
  Used to specify which magic methods are available on object that uses `__call`.
- [`@deprecated`](https://docs.phpdoc.org/latest/references/phpdoc/tags/deprecated.html)
  Used to mark functions, methods, classes and interfaces as being deprecated
- [`@internal`](https://docs.phpdoc.org/latest/references/phpdoc/tags/internal.html)
   used to mark classes, functions and properties that are internal to an application or library.

### Off-label usage of the `@var` tag

The `@var` tag is supposed to only be used for properties. Psalm, taking a lead from PHPStorm and other static analysis tools, allows its use inline in the form `@var Type [VariableReference]`.

If `VariableReference` is provided, it should be of the form `$variable` or `$variable->property`. If used above an assignment, Psalm checks whether the `VariableReference` matches the variable being assigned. If they differ, Psalm will assign the `Type` to `VariableReference` and use it in the expression below.

If no `VariableReference` is given, the annotation tells Psalm that the right hand side of the expression, whether an assignment or a return, is of type `Type`.

```php
<?php
/** @var string */
$a = $_GET['foo'];

/** @var string $b */
$b = $_GET['bar'];

function bat(): string {
    /** @var string */
    return $_GET['bat'];
}
```

## Psalm-specific tags

There are a number of custom tags that determine how Psalm treats your code.

### `@param-out`

This is used to specify that a by-ref type is different from the one that entered. In the function below the first param can be null, but once the function has executed the by-ref value is not null.

```php
<?php
/**
 * @param-out string $s
 */
function addFoo(?string &$s) : void {
    if ($s === null) {
        $s = "hello";
    }
    $s .= "foo";
}
```

### `@psalm-var`, `@psalm-param`, `@psalm-return`, `@psalm-property`, `@psalm-property-read`, `@psalm-property-write`

When specifying types in a format not supported by phpDocumentor ([but supported by Psalm](#type-syntax)) you may wish to prepend `@psalm-` to the PHPDoc tag, so as to avoid confusing your IDE. If a `@psalm`-prefixed tag is given, Psalm will use it in place of its non-prefixed counterpart.

### `@psalm-suppress SomeIssueName`

This annotation is used to suppress issues. It can be used in function docblocks, class docblocks and also inline, applying to the following statement.

Function docblock example:

```php
<?php
/**
 * @psalm-suppress PossiblyNullOperand
 */
function addString(?string $s) {
    echo "hello " . $s;
}
```

Inline example:

```php
<?php
function addString(?string $s) {
    /** @psalm-suppress PossiblyNullOperand */
    echo "hello " . $s;
}
```

`@psalm-suppress all` can be used to suppress all issues instead of listing them individually.

### `@psalm-assert`, `@psalm-assert-if-true` and `@psalm-assert-if-false`

See [Adding assertions](adding_assertions.md).

### `@psalm-ignore-nullable-return`

This can be used to tell Psalm not to worry if a function/method returns null. It’s a bit of a hack, but occasionally useful for scenarios where you either have a very high confidence of a non-null value, or some other function guarantees a non-null value for that particular code path.

```php
<?php
class Foo {}
function takesFoo(Foo $f): void {}

/** @psalm-ignore-nullable-return */
function getFoo(): ?Foo {
  return rand(0, 10000) > 1 ? new Foo() : null;
}

takesFoo(getFoo());
```

### `@psalm-ignore-falsable-return`

This provides the same, but for `false`. Psalm uses this internally for functions like `preg_replace`, which can return false if the given input has encoding errors, but where 99.9% of the time the function operates as expected.

### `@psalm-seal-properties`

If you have a magic property getter/setter, you can use `@psalm-seal-properties` to instruct Psalm to disallow getting and setting any properties not contained in a list of `@property` (or `@property-read`/`@property-write`) annotations.

```php
<?php
/**
 * @property string $foo
 * @psalm-seal-properties
 */
class A {
     public function __get(string $name): ?string {
          if ($name === "foo") {
               return "hello";
          }
     }

     public function __set(string $name, $value): void {}
}

$a = new A();
$a->bar = 5; // this call fails
```

### `@psalm-internal`

Used to mark a class, property or function as internal to a given namespace. Psalm treats this slightly differently to
the PHPDoc `@internal` tag. For `@internal`, an issue is raised if the calling code is in a namespace completely
unrelated to the namespace of the calling code, i.e. not sharing the first element of the namespace.

In contrast for `@psalm-internal`, the docbloc line must specify a namespace. An issue is raised if the calling code
is not within the given namespace.

```php
<?php
namespace A\B {
    /**
    * @internal
    * @psalm-internal A\B
    */
    class Foo { }
}

namespace A\B\C {
    class Bat {
        public function batBat(): void {
            $a = new \A\B\Foo();  // this is fine
        }
    }
}

namespace A\C {
    class Bat {
        public function batBat(): void {
            $a = new \A\B\Foo();  // error
        }
    }
}
```

### `@psalm-readonly` and `@readonly`

Used to annotate a property that can only be written to in its defining class's constructor.

```php
<?php
class B {
  /** @readonly */
  public string $s;

  public function __construct(string $s) {
    $this->s = $s;
  }
}

$b = new B("hello");
echo $b->s;
$b->s = "boo"; // disallowed
```

### `@psalm-mutation-free`

Used to annotate a class method that does not mutate state, either internally or externally of the class's scope.

```php
<?php
class D {
  private string $s;

  public function __construct(string $s) {
    $this->s = $s;
  }

  /**
   * @psalm-mutation-free
   */
  public function getShort() : string {
    return substr($this->s, 0, 5);
  }

  /**
   * @psalm-mutation-free
   */
  public function getShortMutating() : string {
    $this->s .= "hello"; // this is a bug
    return substr($this->s, 0, 5);
  }
}
```

### `@psalm-external-mutation-free`

Used to annotate a class method that does not mutate state externally of the class's scope.

```php
<?php
class E {
  private string $s;

  public function __construct(string $s) {
    $this->s = $s;
  }

  /**
   * @psalm-external-mutation-free
   */
  public function getShortMutating() : string {
    $this->s .= "hello"; // this is fine
    return substr($this->s, 0, 5);
  }

  /**
   * @psalm-external-mutation-free
   */
  public function save() : void {
    file_put_contents("foo.txt", $this->s); // this is a bug
  }
}
```

### `@psalm-immutable`

Used to annotate a class where every property is treated by consumers as `@psalm-readonly` and every instance method is treated as `@psalm-mutation-free`.

```php
<?php
/**
 * @psalm-immutable
 */
abstract class Foo
{
    public string $baz;
  
    abstract public function bar(): int;
}

/**
 * @psalm-immutable
 */
final class ChildClass extends Foo
{
    public function __construct(string $baz)
    {
        $this->baz = $baz;
    }
  
    public function bar(): int
    {
        return 0;
    }
}

$anonymous = new /** @psalm-immutable */ class extends Foo
{
    public string $baz = "B";
  
    public function bar(): int
    {
        return 1;
    }
};
```

### `@psalm-pure`

Used to annotate a [pure function](https://en.wikipedia.org/wiki/Pure_function) - one whose output is just a function of its input.

```php
<?php
class Arithmetic {
  /** @psalm-pure */
  public static function add(int $left, int $right) : int {
    return $left + $right;
  }

  /** @psalm-pure - this is wrong */
  public static function addCumulative(int $left) : int {
    /** @var int */
    static $i = 0; // this is a side effect, and thus a bug
    $i += $left;
    return $i;
  }
}

echo Arithmetic::add(40, 2);
echo Arithmetic::add(40, 2); // same value is emitted

echo Arithmetic::addCumulative(3); // outputs 3
echo Arithmetic::addCumulative(3); // outputs 6
```

### `@pure-callable`

On the other hand, `pure-callable` can be used to denote a callable which needs to be pure.

```php
/**
 * @param pure-callable(mixed): int $callback
 */
function foo(callable $callback) {...}

// this fails since random_int is not pure
foo(
    /** @param mixed $p */
    fn($p) => random_int(1, 2)
);
```

### `@psalm-allow-private-mutation`

Used to annotate readonly properties that can be mutated in a private context. With this, public properties can be read from another class but only be mutated within a method of its own class.

```php
<?php
class Counter {
  /**
   * @readonly
   * @psalm-allow-private-mutation
   */  
  public int $count = 0;
    
  public function increment() : void {
    $this->count++;
  }
}

$counter = new Counter();
echo $counter->count; // outputs 0
$counter->increment(); // Method can mutate property
echo $counter->count; // outputs 1
$counter->count = 5; // This will fail, as it's mutating a property directly
```

### `@psalm-readonly-allow-private-mutation`

This is a shorthand for the property annotations `@readonly` and `@psalm-allow-private-mutation`.

```php
<?php
class Counter {
  /**
   * @psalm-readonly-allow-private-mutation
   */  
  public int $count = 0;
    
  public function increment() : void {
    $this->count++;
  }
}

$counter = new Counter();
echo $counter->count; // outputs 0
$counter->increment(); // Method can mutate property
echo $counter->count; // outputs 1
$counter->count = 5; // This will fail, as it's mutating a property directly
```

### `@psalm-trace`

You can use this annotation to trace inferred type (applied to the *next* statement).

```php
<?php

/** @psalm-trace $username */ 
$username = $_GET['username']; // prints something like "test.php:4 $username: mixed"

```

*Note*: it throws [special low-level issue](../running_psalm/issues/Trace.md), so you have to set errorLevel to 1, override it in config or invoke Psalm with `--show-info=true`.

### `@psalm-taint-*`

See [Security Analysis annotations](../security_analysis/annotations.md).

### `@psalm-type`

This allows you to define an alias for another type.

```php
<?php
/**
 * @psalm-type PhoneType = array{phone: string}
 */
class Phone {
    /**
     * @psalm-return PhoneType
     */
    public function toArray(): array {
        return ["phone" => "Nokia"];
    }
}
```

### `@psalm-import-type`

You can use this annotation to import a type defined with [`@psalm-type`](#psalm-type) if it was defined somewhere else.

```php
<?php
/**
 * @psalm-import-type PhoneType from Phone
 */
class User {
    /**
     * @psalm-return PhoneType
     */
    public function toArray(): array {
        return array_merge([], (new Phone())->toArray());
    }
}
```

You can also alias a type when you import it:

```php
<?php
/**
 * @psalm-import-type PhoneType from Phone as MyPhoneTypeAlias
 */
class User {
    /**
     * @psalm-return MyPhoneTypeAlias
     */
    public function toArray(): array {
        return array_merge([], (new Phone())->toArray());
    }
}
```

## Type Syntax

Psalm supports PHPDoc’s [type syntax](https://docs.phpdoc.org/latest/guides/types.html), and also the [proposed PHPDoc PSR type syntax](https://github.com/php-fig/fig-standards/blob/master/proposed/phpdoc.md#appendix-a-types).

A detailed write-up is found in [Typing in Psalm](typing_in_psalm.md)
