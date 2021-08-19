# scssphp
### <https://scssphp.github.io/scssphp>

![Build](https://github.com/scssphp/scssphp/workflows/CI/badge.svg)
[![License](https://poser.pugx.org/scssphp/scssphp/license)](https://packagist.org/packages/scssphp/scssphp)

`scssphp` is a compiler for SCSS written in PHP.

Checkout the homepage, <https://scssphp.github.io/scssphp>, for directions on how to use.

## Running Tests

`scssphp` uses [PHPUnit](https://github.com/sebastianbergmann/phpunit) for testing.

Run the following command from the root directory to run every test:

    vendor/bin/phpunit tests

There are several tests in the `tests/` directory:

* `ApiTest.php` contains various unit tests that test the PHP interface.
* `ExceptionTest.php` contains unit tests that test for exceptions thrown by the parser and compiler.
* `FailingTest.php` contains tests reported in Github issues that demonstrate compatibility bugs.
* `InputTest.php` compiles every `.scss` file in the `tests/inputs` directory
  then compares to the respective `.css` file in the `tests/outputs` directory.
* `SassSpecTest.php` extracts tests from the `sass/sass-spec` repository.

When changing any of the tests in `tests/inputs`, the tests will most likely
fail because the output has changed. Once you verify that the output is correct
you can run the following command to rebuild all the tests:

    BUILD=1 vendor/bin/phpunit tests

This will compile all the tests, and save results into `tests/outputs`. It also
updates the list of excluded specs from sass-spec.

To enable the full `sass-spec` compatibility tests:

    TEST_SASS_SPEC=1 vendor/bin/phpunit tests

## Coding Standard

`scssphp` source conforms to [PSR12](https://www.php-fig.org/psr/psr-12/).

Run the following command from the root directory to check the code for "sniffs".

    vendor/bin/phpcs --standard=PSR12 --extensions=php bin src tests *.php

## Static Analysis

`scssphp` uses [phpstan](https://phpstan.org/) for static analysis.

Run the following command from the root directory to analyse the codebase:

    make phpstan

As most of the codebase is composed of legacy code which cannot be type-checked
fully, the setup contains a baseline file with all errors we want to ignore. In
particular, we ignore all errors related to not specifying the types inside arrays
when these arrays correspond to the representation of Sass values and Sass AST nodes
in the parser and compiler.
When contributing, the proper process to deal with static analysis is the following:

1. Make your change in the codebase
2. Run `make phpstan`
3. Fix errors reported by phpstan when possible
4. Repeat step 2 and 3 until nothing gets fixed anymore at step 3
5. Run `make phpstan-baseline` to regenerate the phpstan baseline

Additions to the baseline will be reviewed to avoid ignoring errors that should have
been fixed.
