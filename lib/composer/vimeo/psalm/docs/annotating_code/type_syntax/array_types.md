# Array types

In PHP, the `array` type is commonly used to represent three different data structures:

[List](https://en.wikipedia.org/wiki/List_(abstract_data_type)):
```php
<?php
$a = [1, 2, 3, 4, 5];
```

[Associative array](https://en.wikipedia.org/wiki/Associative_array):  
```php
<?php
$a = [0 => 'hello', 5 => 'goodbye'];
$b = ['a' => 'AA', 'b' => 'BB', 'c' => 'CC']
```

Makeshift [Structs](https://en.wikipedia.org/wiki/Struct_(C_programming_language)):
```php
<?php
$a = ['name' => 'Psalm', 'type' => 'tool'];
```

PHP treats all these arrays the same, essentially (though there are some optimisations under the hood for the first case).

Psalm has a few different ways to represent arrays in its type system:

## Generic arrays

Psalm uses a syntax [borrowed from Java](https://en.wikipedia.org/wiki/Generics_in_Java) that allows you denote the types of both keys *and* values:
```php
/** @return array<TKey, TValue> */
```

You can also specify that an array is non-empty with the special type `non-empty-array<TKey, TValue>`.

### PHPDoc syntax

PHPDoc [allows you to specify](https://phpdoc.org/docs/latest/references/phpdoc/types.html#arrays) the  type of values a generic array holds with the annotation:
```php
/** @return ValueType[] */
```

In Psalm this annotation is equivalent to `@psalm-return array<array-key, ValueType>`.

Generic arrays encompass both _associative arrays_ and _lists_.

## Lists

(Psalm 3.6+)

Psalm supports a `list` type that represents continuous, integer-indexed arrays like `["red", "yellow", "blue"]` .

These arrays have the property `$arr === array_values($arr)`, and represent a large percentage of all array usage in PHP applications.

A `list` type is of the form `list<SomeType>`,  where `SomeType` is any permitted [union type](union_types.md) supported by Psalm.

- `list` is a subtype of `array<int, mixed>`
- `list<Foo>` is a subtype of `array<int, Foo>`.

List types show their value in a few ways:

```php
<?php
/**
 * @param array<int, string> $arr
 */
function takesArray(array $arr) : void {
  if ($arr) {
     // this index may not be set
    echo $arr[0];
  }
}

/**
 * @psalm-param list<string> $arr
 */
function takesList(array $arr) : void {
  if ($arr) {
    // list indexes always start from zero,
    // so a non-empty list will have an element here
    echo $arr[0];
  }
}

takesArray(["hello"]); // this is fine
takesArray([1 => "hello"]); // would trigger bug, without warning

takesList(["hello"]); // this is fine
takesList([1 => "hello"]); // triggers warning in Psalm
```

## Object-like arrays

Psalm supports a special format for arrays where the key offsets are known: object-like arrays.

Given an array

```php
<?php
["hello", "world", "foo" => new stdClass, 28 => false];
```

Psalm will type it internally as:

```
array{0: string, 1: string, foo: stdClass, 28: false}
```

You can specify types in that format yourself, e.g.

```php
/** @return array{foo: string, bar: int} */
```

Optional keys can be denoted by a trailing `?`, e.g.:

```php
/** @return array{optional?: string, bar: int} */
```

## Callable arrays

a array holding a callable, like phps native `call_user_func()` and friends supports it:

```php
<?php

$callable = ['myClass', 'aMethod'];
$callable = [$object, 'aMethod'];
```

## non-empty-array

a array which is not allowed to be empty
