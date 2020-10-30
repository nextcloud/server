# Adding assertions

Psalm has three docblock annotations that allow you to specify that a function verifies facts about variables and properties:

- `@psalm-assert` (used when throwing an exception)
- `@psalm-assert-if-true`/`@psalm-assert-if-false` (used when returning a `bool`)

A list of acceptable assertions [can be found here](assertion_syntax.md).

## Examples

If you have a class that verified its input is an array of strings, you can make that clear to Psalm:

```php
<?php
/** @psalm-assert string[] $arr */
function validateStringArray(array $arr) : void {
    foreach ($arr as $s) {
        if (!is_string($s)) {
          throw new UnexpectedValueException('Invalid value ' . gettype($s));
        }
    }
}
```

This enables you to call the `validateStringArray` function on some data and have Psalm understand that the given data *must* be an array of strings:

```php
<?php
function takesString(string $s) : void {}
function takesInt(int $s) : void {}

function takesArray(array $arr) : void {
    takesInt($arr[0]); // this is fine

    validateStringArray($arr);

    takesInt($arr[0]); // this is an error

    foreach ($arr as $a) {
        takesString($a); // this is fine
    }
}
```

Similarly, `@psalm-assert-if-true` and `@psalm-assert-if-false` will filter input if the function/method returns `true` and `false` respectively:

```php
<?php
class A {
    public function isValid() : bool {
        return (bool) rand(0, 1);
    }
}
class B extends A {
    public function bar() : void {}
}

/**
 * @psalm-assert-if-true B $a
 */
function isValidB(A $a) : bool {
    return $a instanceof B && $a->isValid();
}

/**
 * @psalm-assert-if-false B $a
 */
function isInvalidB(A $a) : bool {
    return $a instanceof B || !$a->isValid();
}

function takesA(A $a) : void {
    if (isValidB($a)) {
        $a->bar();
    }

    if (isInvalidB($a)) {
        // do something
    } else {
        $a->bar();
    }

    $a->bar(); //error
}
```

As well as getting Psalm to understand that the given data must be a certain type, you can also show that a variable must be not null:

```php
<?php
/**
 * @psalm-assert !null $value
 */
function assertNotNull($value): void {
  // Some check that will mean the method will only complete if $value is not null.
}
```

And you can check on null values:

```php
<?php
/**
 * @psalm-assert-if-true null $value
 */
function isNull($value): bool {
  return ($value === null);
}
```
