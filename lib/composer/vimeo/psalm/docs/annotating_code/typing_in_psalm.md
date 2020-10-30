# Typing in Psalm

Psalm is able to interpret all PHPDoc type annotations, and use them to further understand the codebase.

Types are used to describe acceptable values for properties, variables, function parameters and `return $x`.

## Docblock Type Syntax

Psalm allows you to express a lot of complicated type information in docblocks.

All docblock types are either [atomic types](type_syntax/atomic_types.md), [union types](type_syntax/union_types.md) or [intersection types](type_syntax/intersection_types.md).

Additionally Psalm supports PHPDoc’s [type syntax](https://docs.phpdoc.org/guides/types.html), and also the [proposed PHPDoc PSR type syntax](https://github.com/php-fig/fig-standards/blob/master/proposed/phpdoc.md#appendix-a-types).

## Property declaration types vs Assignment typehints

You can use the `/** @var Type */` docblock to annotate both [property declarations](http://php.net/manual/en/language.oop5.properties.php) and to help Psalm understand variable assignment.

### Property declaration types

You can specify a particular type for a class property declaration in Psalm by using the `@var` declaration:

```php
<?php
/** @var string|null */
public $foo;
```

When checking `$this->foo = $some_variable;`, Psalm will check to see whether `$some_variable` is either `string` or `null` and, if neither, emit an issue.

If you leave off the property type docblock, Psalm will emit a `MissingPropertyType` issue.

### Assignment typehints

Consider the following code:

```php
<?php
namespace YourCode {
  function bar() : int {
    $a = \ThirdParty\foo();
    return $a;
  }
}
namespace ThirdParty {
  function foo() {
    return mt_rand(0, 100);
  }
}
```

Psalm does not know what the third-party function `ThirdParty\foo` returns, because the author has not added any return types. If you know that the function returns a given value you can use an assignment typehint like so:

```php
<?php
namespace YourCode {
  function bar() : int {
    /** @var int */
    $a = \ThirdParty\foo();
    return $a;
  }
}
namespace ThirdParty {
  function foo() {
    return mt_rand(0, 100);
  }
}
```

This tells Psalm that `int` is a possible type for `$a`, and allows it to infer that `return $a;` produces an integer.

Unlike property types, however, assignment typehints are not binding – they can be overridden by a new assignment without Psalm emitting an issue e.g.

```php
<?php
/** @var string|null */
$a = foo();
$a = 6; // $a is now typed as an int
```

You can also use typehints on specific variables e.g.

```php
<?php
/** @var string $a */
echo strpos($a, 'hello');
```

This tells Psalm to assume that `$a` is a string (though it will still throw an error if `$a` is undefined).

## Specifying string/int options (aka enums)

Psalm allows you to specify a specific set of allowed string/int values for a given function or method.

Whereas this would cause Psalm to [complain that not all paths return a value](https://getpsalm.org/r/9f6f1ceab6):

```php
<?php
function foo(string $s) : string {
  switch ($s) {
    case 'a':
      return 'hello';

    case 'b':
      return 'goodbye';
  }
}
```

If you specify the param type of `$s` as `'a'|'b'` Psalm will know that all paths return a value:

```php
<?php
/**
 * @param 'a'|'b' $s
 */
function foo(string $s) : string {
  switch ($s) {
    case 'a':
      return 'hello';

    case 'b':
      return 'goodbye';
  }
}
```

If the values are in class constants, you can use those too:

```php
<?php
class A {
  const FOO = 'foo';
  const BAR = 'bar';
}

/**
 * @param A::FOO | A::BAR $s
 */
function foo(string $s) : string {
  switch ($s) {
    case A::FOO:
      return 'hello';

    case A::BAR:
      return 'goodbye';
  }
}
```

If the class constants share a common prefix, you can specify them all using a wildcard:

```php
<?php
class A {
  const STATUS_FOO = 'foo';
  const STATUS_BAR = 'bar';
}

/**
 * @param A::STATUS_* $s
 */
function foo(string $s) : string {
  switch ($s) {
    case A::STATUS_FOO:
      return 'hello';

    default:
      // any other status
      return 'goodbye';
  }
}
```
