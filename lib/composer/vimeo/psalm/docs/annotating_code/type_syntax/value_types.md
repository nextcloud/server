# Value types

Psalm also allows you to specify values in types.

### null

This is the `null` value, destroyer of worlds. Use it sparingly. Psalm supports you writing `?Foo` to mean `null|Foo`.

### true, false

Use of `true` and `false` is also PHPDoc-compatible

### "some_string", 4, 3.14

Psalm also allows you specify literal values in types, e.g. `@return "good"|"bad"`

### Regular class constants

Psalm allows you to include class constants in types, e.g. `@return Foo::GOOD|Foo::BAD`. You can also specify explicit class strings e.g. `Foo::class|Bar::class`

If you want to specify that a parameter should only take class strings that are, or extend, a given class, you can use the annotation `@param class-string<Foo> $foo_class`. If you only want the param to accept that exact class string, you can use the annotation `Foo::class`:

```php
<?php
class A {}
class AChild extends A {}
class B {}
class BChild extends B {}

/**
 * @param class-string<A>|class-string<B> $s
 */
function foo(string $s) : void {}

/**
 * @param A::class|B::class $s
 */
function bar(string $s) : void {}

foo(A::class); // works
foo(AChild::class); // works
foo(B::class); // works
foo(BChild::class); // works
bar(A::class); // works
bar(AChild::class); // fails
bar(B::class); // works
bar(BChild::class); // fails
```
