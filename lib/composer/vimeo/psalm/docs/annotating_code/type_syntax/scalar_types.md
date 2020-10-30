# Scalar types

`int`, `bool`, `float`, `string` are examples of scalar types. Scalar types represent scalar values in PHP. These types are also valid types in PHP 7.

### scalar

The type `scalar` is the supertype of all scalar types.

### array-key

`array-key` is the supertype (but not a union) of `int` and `string`.

### positive-int

`positive-int` allows only positive integers

### numeric

`numeric` is a supertype of `int` or `float` and [`numeric-string`](#numeric-string).

### class-string, interface-string

Psalm supports a special meta-type for `MyClass::class` constants, `class-string`, which can be used everywhere `string` can.

For example, given a function with a `string` parameter `$class_name`, you can use the annotation `@param class-string $class_name` to tell Psalm make sure that the function is always called with a `::class` constant in that position:

```php
<?php
class A {}

/**
 * @param class-string $s
 */
function takesClassName(string $s) : void {}
```

`takesClassName("A");` would trigger a `TypeCoercion` issue, whereas `takesClassName(A::class)` is fine.

You can also parameterize `class-string` with an object name e.g. [`class-string<Foo>`](value_types.md#regular-class-constants). This tells Psalm that any matching type must either be a class string of `Foo` or one of its descendants.

### trait-string

Psalm also supports a `trait-string` annotation denote a trait that exists.

### callable-string

`callable-string` denotes a string value that has passed an `is_callable` check.

### numeric-string

`numeric-string` denotes a string value that has passed an `is_numeric` check.

### lowercase-string, non-empty-string, non-empty-lowercase-string

an empty string, lowercased or both at once.

### html-escaped-string

A string which can safely be used in a html context
