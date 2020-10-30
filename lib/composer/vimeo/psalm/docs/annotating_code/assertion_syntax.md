# Assertion syntax

Psalm’s [assertion annotation](adding_assertions.md) supports a number of different assertions.

Psalm assertions are of the form

`@psalm-assert(-if-true|-if-false)? (Assertion) (Variable or Property)`

`Assertion` here can have many forms:

## Regular assertions

### is_xxx assertions

Most `is_xxx` PHP functions have companion assertions e.g. `int` for `is_int`. Here's the full list:

- `int`
- `float`
- `string`
- `bool`
- `scalar`
- `callable`
- `countable`
- `array`
- `iterable`
- `numeric`
- `resource`
- `object`
- `null`

So a custom version `is_int` could be annotated in Psalm as

```php
<?php
/** @psalm-assert-if-true int $x */
function custom_is_int($x) {
  return is_int($x);
}
```

### Object type assertions

Any class can be used as an assertion e.g.

`@psalm-assert SomeObjectType $foo`

### Generic assertions

Generic type parameters can also now be asserted e.g.

`@psalm-assert array<int, string> $foo`

## Negated assertions

Any assertion above can be negated:

This asserts that `$foo` is not an `int`:

```php
<?php
/** @psalm-assert !int $foo */
```

This asserts that `$bar` is not an object of type `SomeObjectType`:
```php
<?php
/** @psalm-assert !SomeObjectType $bar  */
```

## Bool assertions

This asserts that `$bar` is `true`:
```php
<?php
/** @psalm-assert true $bar  */
```

This asserts that `$bar` is not `false`:
```php
<?php
/** @psalm-assert !false $bar  */
```

## Equality assertions

Psalm also supports the equivalent of `assert($some_int === $other_int)` in the form
```php
<?php
/** @psalm-assert =int $some_int */
```

There are two differences between the above assertion and 

```php
<?php
/** @psalm-assert int $some_int */
```

Firstly, the negation of `=int` has no meaning:

```php
<?php
/** @psalm-assert-if-true =int $x */
function equalsFive($x) {
  return is_int($x) && $x === 5;
}

function foo($y) : void {
  if (equalsFive($y)) {
    // $y is definitely an int
  } else {
    // $y might be an int, but it might not
  }
}

function bar($y) : void {
  if (is_int($y)) {
    // $y is definitely an int
  } else {
    // $y is definitely not an int
  }
}
```

Secondly, calling `equalsFive($some_int)` is not a `RedundantCondition` in Psalm, whereas calling `is_int($some_int)` is.


