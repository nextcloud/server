# Union Types

An annotation of the form `Type1|Type2|Type3` is a _Union Type_. `Type1`, `Type2` and `Type3` are all acceptable possible types of that union type.

`Type1`, `Type2` and `Type3` are each [atomic types](atomic_types.md).

Union types can be generated in a number of different ways, for example in ternary expressions:

```php
<?php
$rabbit = rand(0, 10) === 4 ? 'rabbit' : ['rabbit'];
```

`$rabbit` will be either a `string` or an `array`. We can represent that idea with Union Types – so `$rabbit` is typed as `string|array`. Union types represent *all* the possible types a given variable can have.

PHP builtin functions also have union-type returns - `strpos` can return `false` in some situations, `int` in others. We represent that union type with `int|false`.
