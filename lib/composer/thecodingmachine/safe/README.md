[![Latest Stable Version](https://poser.pugx.org/thecodingmachine/safe/v/stable.svg)](https://packagist.org/packages/thecodingmachine/safe)
[![Total Downloads](https://poser.pugx.org/thecodingmachine/safe/downloads.svg)](https://packagist.org/packages/thecodingmachine/safe)
[![Latest Unstable Version](https://poser.pugx.org/thecodingmachine/safe/v/unstable.svg)](https://packagist.org/packages/thecodingmachine/safe)
[![License](https://poser.pugx.org/thecodingmachine/safe/license.svg)](https://packagist.org/packages/thecodingmachine/safe)
[![Build Status](https://travis-ci.org/thecodingmachine/safe.svg?branch=master)](https://travis-ci.org/thecodingmachine/safe)
[![Continuous Integration](https://github.com/thecodingmachine/safe/workflows/Continuous%20Integration/badge.svg)](https://github.com/thecodingmachine/safe/actions)
[![codecov](https://codecov.io/gh/thecodingmachine/safe/branch/master/graph/badge.svg)](https://codecov.io/gh/thecodingmachine/safe)

Safe PHP
========

**Work in progress**

A set of core PHP functions rewritten to throw exceptions instead of returning `false` when an error is encountered.

## The problem

Most PHP core functions were written before exception handling was added to the language. Therefore, most PHP functions
do not throw exceptions. Instead, they return `false` in case of error.

But most of us are too lazy to check explicitly for every single return of every core PHP function.

```php
// This code is incorrect. Twice.
// "file_get_contents" can return false if the file does not exists
// "json_decode" can return false if the file content is not valid JSON
$content = file_get_contents('foobar.json');
$foobar = json_decode($content);
```

The correct version of this code would be:

```php
$content = file_get_contents('foobar.json');
if ($content === false) {
    throw new FileLoadingException('Could not load file foobar.json');
}
$foobar = json_decode($content);
if (json_last_error() !== JSON_ERROR_NONE) {
    throw new FileLoadingException('foobar.json does not contain valid JSON: '.json_last_error_msg());
}
```

Obviously, while this snippet is correct, it is less easy to read.

## The solution

Enter *thecodingmachine/safe* aka Safe-PHP.

Safe-PHP redeclares all core PHP functions. The new PHP functions act exactly as the old ones, except they
throw exceptions properly when an error is encountered. The "safe" functions have the same name as the core PHP
functions, except they are in the `Safe` namespace.

```php
use function Safe\file_get_contents;
use function Safe\json_decode;

// This code is both safe and simple!
$content = file_get_contents('foobar.json');
$foobar = json_decode($content);
```

All PHP functions that can return `false` on error are part of Safe.
In addition, Safe also provide 2 'Safe' classes: `Safe\DateTime` and `Safe\DateTimeImmutable` whose methods will throw exceptions instead of returning false.

## PHPStan integration

> Yeah... but I must explicitly think about importing the "safe" variant of the function, for each and every file of my application.
> I'm sure I will forget some "use function" statements!

Fear not! thecodingmachine/safe comes with a PHPStan rule.

Never heard of [PHPStan](https://github.com/phpstan/phpstan) before?
Check it out, it's an amazing code analyzer for PHP.

Simply install the Safe rule in your PHPStan setup (explained in the "Installation" section) and PHPStan will let you know each time you are using an "unsafe" function.

The code below will trigger this warning:

```php
$content = file_get_contents('foobar.json');
```

> Function file_get_contents is unsafe to use. It can return FALSE instead of throwing an exception. Please add 'use function Safe\\file_get_contents;' at the beginning of the file to use the variant provided by the 'thecodingmachine/safe' library.

## Installation

Use composer to install Safe-PHP:

```bash
$ composer require thecodingmachine/safe
```

*Highly recommended*: install PHPStan and PHPStan extension:

```bash
$ composer require --dev thecodingmachine/phpstan-safe-rule
```

Now, edit your `phpstan.neon` file and add these rules:

```yml
includes:
    - vendor/thecodingmachine/phpstan-safe-rule/phpstan-safe-rule.neon
```

## Automated refactoring

You have a large legacy codebase and want to use "Safe-PHP" functions throughout your project? PHPStan will help you
find these functions but changing the namespace of the functions one function at a time might be a tedious task.

Fortunately, Safe comes bundled with a "Rector" configuration file. [Rector](https://github.com/rectorphp/rector) is a command-line
tool that performs instant refactoring of your application.

Run

```bash
$ composer require --dev rector/rector:^0.7
```

to install `rector/rector`.

Run

```bash
vendor/bin/rector process src/ --config vendor/thecodingmachine/safe/rector-migrate-0.7.php
```

to run `rector/rector`.

*Note:* do not forget to replace "src/" with the path to your source directory.

**Important:** the refactoring only performs a "dumb" replacement of functions. It will not modify the way
"false" return values are handled. So if your code was already performing error handling, you will have to deal
with it manually.

Especially, you should look for error handling that was already performed, like:

```php
if (!mkdir($dirPath)) {
    // Do something on error
}
```

This code will be refactored by Rector to:

```php
if (!\Safe\mkdir($dirPath)) {
    // Do something on error
}
```

You should then (manually) refactor it to:

```php
try {
    \Safe\mkdir($dirPath));
} catch (\Safe\FilesystemException $e) {
    // Do something on error
}
```

## Performance impact

Safe is loading 1000+ functions from ~85 files on each request. Yet, the performance impact of this loading is quite low.

In case you worry, using Safe will "cost" you ~700µs on each request. The [performance section](performance/README.md)
contains more information regarding the way we tested the performance impact of Safe.

## Learn more

Read [the release article on TheCodingMachine's blog](https://thecodingmachine.io/introducing-safe-php) if you want to
learn more about what triggered the development of Safe-PHP.

## Contributing

The files that contain all the functions are auto-generated from the PHP doc.
Read the [CONTRIBUTING.md](CONTRIBUTING.md) file to learn how to regenerate these files and to contribute to this library.
