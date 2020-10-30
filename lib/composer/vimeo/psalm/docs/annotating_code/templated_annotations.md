# Templating

Docblocks allow you to tell Psalm some simple information about how your code works. For example `@return int` in a function return type tells Psalm that a function should return an `int` and `@return MyContainer` tells Psalm that a function should return an instance of a user-defined class `MyContainer`. In either case, Psalm can check that the function actually returns those types _and_ that anything calling that function uses its returned value properly.

Templated types allow you to tell Psalm even more information about how your code works.

Let's look at a simple class `MyContainer`:

```php
<?php
class MyContainer {
  private $value;

  public function __construct($value) {
    $this->value = $value;
  }

  public function getValue() {
    return $this->value;
  }
}
```

When Psalm handles the return type of `$my_container->getValue()` it doesn't know what it's getting out, because the value can be arbitrary.

Templated annotations provide us with a workaround - we can define a generic/templated param `T` that is a placeholder for the value inside `MyContainer`:

```php
<?php
/**
 * @template T
 */
class MyContainer {
  /** @var T */
  private $value;

  /** @param T $value */
  public function __construct($value) {
    $this->value = $value;
  }

  /** @return T */
  public function getValue() {
    return $this->value;
  }
}
```

Now we can substitute values for that templated param when we reference `MyContainer` in docblocks e.g. `@return MyContainer<int>`. This tells Psalm to substitute `T` for `int` when evaluating that return type, effectively treating it as a class that looks like

```php
<?php
class One_off_instance_of_MyContainer {
  /** @var int */
  private $value;

  /** @param int $value */
  public function __construct($value) {
    $this->value = $value;
  }

  /** @return int */
  public function getValue() {
    return $this->value;
  }
}
```

This pattern can be used in large number of different situations like mocking, collections, iterators and loading arbitrary objects. Psalm has a large number of annotations to make it easy to use templated types in your codebase.

## `@template`

The `@template` tag allows classes and functions to declare a generic type parameter.

As a very simple example, this function returns whatever is passed in:

```php
<?php
/**
 * @template T
 * @psalm-param T $t
 * @return T
 */
function mirror($t) {
    return $t;
}

$a = 5;
$b = mirror($a); // Psalm knows the result is an int

$c = "foo";
$d = mirror($c); // Psalm knows the result is string
```

Psalm also uses `@template` annotations in its stubbed versions of PHP array functions e.g.

```php
<?php
/**
 * Takes one array with keys and another with values and combines them
 *
 * @template TKey
 * @template TValue
 *
 * @param array<mixed, TKey> $arr
 * @param array<mixed, TValue> $arr2
 * @return array<TKey, TValue>
 */
function array_combine(array $arr, array $arr2) {}
```

### Notes
- `@template` tag order matters for class docblocks, as they dictate the order in which those generic parameters are referenced in docblocks.
- The names of your templated types (e.g. `TKey`, `TValue` don't matter outside the scope of the class or function in which they're declared.

## @param class-string&lt;T&gt;

Psalm also allows you to parameterize class types

```php
<?php
/**
 * @template T
 * @psalm-param class-string<T> $class
 * @return T
 */
function instantiator(string $class) {
    return new $class();
}

class Foo {}

$a = instantiator(Foo::class); // Psalm knows the result is an object of type Foo
```

## Template inheritance

Psalm allows you to extend templated classes with `@extends`/`@template-extends`:

```php
<?php
/**
 * @template T
 */
class ParentClass {}

/**
 * @extends ParentClass<int>
 */
class ChildClass extends ParentClass {}
```

similarly you can implement interfaces with `@implements`/`@template-implements`

```php
<?php
/**
 * @template T
 */
interface IFoo {}

/**
 * @implements IFoo<int>
 */
class Foo implements IFoo {}
```

and import traits with `@use`/`@template-use`

```php
<?php
/**
 * @template T
 */
trait MyTrait {}

class Foo {
    /**
     * @use MyTrait<int>
     */
    use MyTrait;
}
```

You can also extend one templated class with another, e.g.

```php
<?php
/**
 * @template T1
 */
class ParentClass {}

/**
 * @template T2
 * @extends ParentClass<T2>
 */
class ChildClass extends ParentClass {}
```

## Template constraints

You can use `@template of <type>` to restrict input. For example, to restrict to a given class you can use

```php
<?php
class Foo {}
class FooChild extends Foo {}

/**
 * @template T of Foo
 * @psalm-param T $t
 * @return array<int, T>
 */
function makeArray($t) {
    return [$t];
}
$a = makeArray(new Foo()); // typed as array<int, Foo>
$b = makeArray(new FooChild()); // typed as array<int, FooChild>
$c = makeArray(new stdClass()); // type error
```

Templated types aren't limited to key-value pairs, and you can re-use templates across multiple arguments of a template-supporting type:
```php
<?php
/**
 * @template T0 as array-key
 *
 * @template-implements IteratorAggregate<T0, int>
 */
abstract class Foo implements IteratorAggregate {
  /**
   * @var int
   */
  protected $rand_min;

  /**
   * @var int
   */
  protected $rand_max;

  public function __construct(int $rand_min, int $rand_max) {
    $this->rand_min = $rand_min;
    $this->rand_max = $rand_max;
  }

  /**
   * @return Generator<T0, int, mixed, T0>
   */
  public function getIterator() : Generator {
    $j = random_int($this->rand_min, $this->rand_max);
    for($i = $this->rand_min; $i <= $j; $i += 1) {
      yield $this->getFuzzyType($i) => $i ** $i;
    }

    return $this->getFuzzyType($j);
  }

  /**
   * @return T0
   */
  abstract protected function getFuzzyType(int $i);
}

/**
 * @template-extends Foo<int>
 */
class Bar extends Foo {
  protected function getFuzzyType(int $i) : int {
    return $i;
  }
}

/**
 * @template-extends Foo<string>
 */
class Baz extends Foo {
  protected function getFuzzyType(int $i) : string {
    return static::class . '[' . $i . ']';
  }
}
```

## Template covariance

Imagine you have code like this:

```php
<?php
class Animal {}
class Dog extends Animal {}
class Cat extends Animal {}

/**
 * @template T
 */
class Collection {
    /**
     * @var array<int, T>
     */
    public array $list;

    /**
     * @param array<int, T> $list
     */
    public function __construct(array $list) {
        $this->list = $list;
    }

    /**
     * @param T $t
     */
    public function add($t) : void {
        $this->list[] = $t;
    }
}

/**
 * @param Collection<Animal> $collection
 */
function addAnimal(Collection $collection) : void {
    $collection->add(new Cat());
}

/**
 * @param Collection<Dog> $dog_collection
 */
function takesDogList(Collection $dog_collection) : void {
    addAnimal($dog_collection);
}
```

That last call `addAnimal($dog_collection)` breaks the type of the collection – suddenly a collection of dogs becomes a collection of dogs _or_ cats. That is bad.

To prevent this, Psalm emits an error when calling `addAnimal($dog_collection)` saying "addAnimal expects a `Collection<Animal>`, but `Collection<Dog>` was passed". If you haven't encountered this rule before it's probably confusing to you – any function that accepted an `Animal` would be happy to accept a subtype thereof. But as we see in the example above, doing so can lead to problems.

But there are also times where it's perfectly safe to pass template param subtypes:

```php
<?php
abstract class Animal {
    abstract public function getNoise() : string;
}
class Dog extends Animal {
    public function getNoise() : string { return "woof"; }
}
class Cat extends Animal {
    public function getNoise() : string { return "miaow"; }
}

/**
 * @template T
 */
class Collection {
    /** @var array<int, T> */
    public array $list = [];
}

/**
 * @param Collection<Animal> $collection
 */
function getNoises(Collection $collection) : void {
    foreach ($collection->list as $animal) {
        echo $animal->getNoise();
    }
}

/**
 * @param Collection<Dog> $dog_collection
 */
function takesDogList(Collection $dog_collection) : void {
    getNoises($dog_collection);
}
```

Here we're not doing anything bad – we're just iterating over an array of objects. But Psalm still gives that same basic error – "getNoises expects a `Collection<Animal>`, but `Collection<Dog>` was passed".

We can tell Psalm that it's safe to pass subtypes for the templated param `T` by using the annotation `@template-covariant T`:

```php
<?php
/**
 * @template-covariant T
 */
class Collection {
    /** @var array<int, T> */
    public array $list = [];
}
```

Doing this for the above example produces no errors: [https://psalm.dev/r/5254af7a8b](https://psalm.dev/r/5254af7a8b)

But `@template-covariant` doesn't get rid of _all_ errors – if you add it to the first example, you get a new error – [https://psalm.dev/r/0fcd699231](https://psalm.dev/r/0fcd699231) – complaining that you're attempting to use a covariant template parameter for function input. That’s no good, as it means you're likely altering the collection somehow (which is, again, a violation).

### But what about immutability?

Psalm has [comprehensive support for declaring functional immutability](https://psalm.dev/articles/immutability-and-beyond).

If we make sure that the class is immutable, we can declare a class with an `add` method that still takes a covariant param as input, but which does not modify the collection at all, instead returning a new one:

```php
<?php
/**
 * @template-covariant T
 * @psalm-immutable
 */
class Collection {
    /**
     * @var array<int, T>
     */
    public array $list = [];

    /**
     * @param array<int, T> $list
     */
    public function __construct(array $list) {
        $this->list = $list;
    }

    /**
     * @param T $t
     * @return Collection<T>
     */
    public function add($t) : Collection {
        return new Collection(array_merge($this->list, [$t]));
    }
}
```

This is perfectly valid, and Psalm won't complain.

## Builtin templated classes and interfaces

Psalm has support for a number of builtin classes and interfaces that you can extend/implement in your own code.

- `interface Traversable<TKey, TValue>`
- `interface ArrayAccess<TKey, TValue>`
- `interface IteratorAggregate<TKey, TValue> extends Traversable<TKey, TValue>`
- `interface Iterator<TKey, TValue> extends Traversable<TKey, TValue>`
- `interface SeekableIterator<TKey, TValue> extends Iterator<TKey, TValue>`

- `class Generator<TKey, TValue, TSend, TReturn> extends Traversable<TKey, TValue>`
- `class ArrayObject<TKey, TValue> implements IteratorAggregate<TKey, TValue>, ArrayAccess<TKey, TValue>`
- `class ArrayIterator<TKey of array-key, TValue> implements SeekableIterator<TKey, TValue>, ArrayAccess<TKey, TValue>`
- `class DOMNodeList<TNode of DOMNode> implements Traversable<int, TNode>`
- `class SplDoublyLinkedList<TKey, TValue> implements Iterator<TKey, TValue>, ArrayAccess<TKey, TValue>`
- `class SplQueue<TValue> extends SplDoublyLinkedList<int, TValue>`
