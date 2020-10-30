# Refactoring Code

Sometimes you want to make big changes to your codebase like moving methods or classes.

Psalm has a refactoring tool you can access with either `vendor/bin/psalm-refactor` or `vendor/bin/psalm --refactor`, followed by appropriate commands.

## Moving all classes from one namespace to another

```
vendor/bin/psalm-refactor --move "Ns1\*" --into "Some\Other\Namespace"
```

This moves all classes in `Ns1` (e.g. `Ns1\Foo`, `Ns1\Baz`) into the specified namespace. Files are moved as necessary.

## Moving classes between namespaces

```
vendor/bin/psalm-refactor --move "Ns1\Foo" --into "Ns2"
```

This tells Psalm to move class `Ns1\Foo` into the namespace `Ns2`, so any reference to `Ns1\Foo` becomes `Ns2\Foo`). Files are moved as necessary.

## Moving and renaming classes

```
vendor/bin/psalm-refactor --rename "Ns1\Foo" --to "Ns2\Bar\Baz"
```

This tells Psalm to move class `Ns1\Foo` into the namespace `Ns2\Bar` and rename it to `Baz`, so any reference to `Ns1\Foo` becomes `Ns2\Bar\Baz`). Files are moved as necessary.

## Moving methods between classes

```
vendor/bin/psalm-refactor --move "Ns1\Foo::bar" --into "Ns2\Baz"
```

This tells Psalm to move a method named `bar` inside `Ns1\Foo` into the class `Ns2\Baz`, so any reference to `Ns1\Foo::bar` becomes `Ns2\Baz::bar`). Psalm currently allows you to move static methods between aribitrary classes, and instance methods into child classes of that instance.

## Moving and renaming methods

```
vendor/bin/psalm-refactor --rename "Ns1\Foo::bar" --to "Ns2\Baz::bat"
```

This tells Psalm to move method `Ns1\Foo::bar` into the class `Ns2\Baz` and rename it to `bat`, so any reference to `Ns1\Foo::bar` becomes `Ns2\Baz::bat`).
