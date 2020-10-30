# Conditional types

Psalm supports the equivalent of TypeScript’s [conditional types](https://www.typescriptlang.org/docs/handbook/advanced-types.html#conditional-types).

Conditional types have the form:

`(<template param> is <union type> ? <union type> : <union type>)`

All conditional types must be wrapped inside brackets e.g. `(...)`

Conditional types are dependent on [template parameters](../templated_annotations.md), so you can only use them in a function where template parameters are defined.

## Example application

Let's suppose we want to make a userland implementation of PHP's numeric addition (but please never do this). You could type this with a conditional return type:

```php
<?php

/**
 * @template T as int|float
 * @param T $a
 * @param T $b
 * @return int|float
 * @psalm-return (T is int ? int : float)
 */
function add($a, $b) {
    return $a + $b;
}
```

When figuring out the result of `add($x, $y)` Psalm tries to infer the value `T` for that particular call. When calling `add(1, 2)`, `T` can be trivially inferred as an `int`. Then Psalm takes the provided conditional return type

`(T is int ? int : float)`

and substitutes in the known value of `T`, `int`, so that expression becomes

`(int is int ? int : float)`

which simplifies to `(true ? int : float)`, which simplifies to `int`.

Calling `add(1, 2.1)` means `T` would instead be inferred as `int|float`, which means the expression `(T is int ? int : float)` would instead have the substitution

`(int|float is int ? int : float)`

The union `int|float` is clearly not an `int`, so the expression is simplified to `(false ? int : float)`, which simplifies to `float`.

## Nested conditionals

You can also nest conditionals just as you could ternary expressions:

```php
<?php

class A {
    const TYPE_STRING = 0;
    const TYPE_INT = 1;

    /**
     * @template T as int
     * @param T $i
     * @psalm-return (
     *     T is self::TYPE_STRING
     *     ? string
     *     : (T is self::TYPE_INT ? int : bool)
     * )
     */
    public static function getDifferentType(int $i) {
        if ($i === self::TYPE_STRING) {
            return "hello";
        }

        if ($i === self::TYPE_INT) {
            return 5;
        }

        return true;
    }
}


```

Calling `getDifferentType(0)` will 
