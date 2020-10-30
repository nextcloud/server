Cookbook - Making a new Fixer for PHP CS Fixer
==============================================

You want to make a new fixer to PHP CS Fixer and do not know how to
start. Follow this document and you will be able to do it.

## Background

In order to be able to create a new fixer, you need some background.
PHP CS Fixer is a transcompiler which takes valid PHP code and pretty
print valid PHP code. It does all transformations in multiple passes,
a.k.a., multi-pass compiler.

Therefore, a new fixer is meant to be ideally
[idempotent](https://en.wikipedia.org/wiki/Idempotence#Computer_science_meaning),
or at least atomic in its actions. More on this later.

All contributions go through a code review process. Do not feel
discouraged - it is meant only to give more people more chance to
contribute, and to detect bugs ([Linus'
Law](https://en.wikipedia.org/wiki/Linus%27s_Law)).

If possible, try to get acquainted with the public interface for the
[Tokens class](/src/Tokenizer/Tokens.php)
and [Token class](/src/Tokenizer/Token.php)
classes.

## Assumptions

* You are familiar with Test Driven Development.
* Forked FriendsOfPHP/PHP-CS-Fixer into your own GitHub Account.
* Cloned your forked repository locally.
* Installed the dependencies of PHP CS Fixer using [Composer](https://getcomposer.org/).
* You have read [`CONTRIBUTING.md`](/CONTRIBUTING.md).

## Step by step

For this step-by-step, we are going to create a simple fixer that
removes all comments of the code that are preceded by ';' (semicolon).

We are calling it `remove_comments` (code name), or,
`RemoveCommentsFixer` (class name).

### Step 1 - Creating files

Create a new file in `src/Fixer/Comment/RemoveCommentsFixer.php`.
Put this content inside:
```php
<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Fixer\Comment;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Your name <your@email.com>
 */
final class RemoveCommentsFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        // Return a definition of the fixer, it will be used in the README.rst.
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        // Check whether the collection is a candidate for fixing.
        // Has to be ultra cheap to execute.
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        // Add the fixing logic of the fixer here.
    }
}
```

Note how the class and file name match. Also keep in mind that all
fixers must implement `Fixer\FixerInterface`. In this case, the fixer is
inheriting from `AbstractFixer`, which fulfills the interface with some
default behavior.

Now let us create the test file at
`tests/Fixer/Comment/RemoveCommentsFixerTest.php`. Put this content inside:

```php
<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Tests\Fixer\Comment;

use PhpCsFixer\Tests\Test\AbstractFixerTestCase;

/**
 * @author Your name <your@email.com>
 *
 * @internal
 *
 * @covers \PhpCsFixer\Fixer\Comment\RemoveCommentsFixer
 */
final class RemoveCommentsFixerTest extends AbstractFixerTestCase
{
    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases()
    {
        return [];
    }
}
```
### Step 2 - Using tests to define fixers behavior

Now that the files are created, you can start writing test to define the
behavior of the fixer. You have to do it in two ways: first, ensuring
the fixer changes what it should be changing; second, ensuring that
fixer does not change what is not supposed to change. Thus:

#### Keeping things as they are:
`tests/Fixer/Comment/RemoveCommentsFixerTest.php`@provideFixCases:
```php
    ...
    public function provideFixCases()
    {
        return [
            ['<?php echo "This should not be changed";'], // Each sub-array is a test
        ];
    }
    ...
```

#### Ensuring things change:
`tests/Fixer/Comment/RemoveCommentsFixerTest.php`@provideFixCases:
```php
    ...
    public function provideFixCases()
    {
        return [
            [
                '<?php echo "This should be changed"; ', // This is expected output
                '<?php echo "This should be changed"; /* Comment */', // This is input
            ],
        ];
    }
    ...
```

Note that expected outputs are **always** tested alone to ensure your fixer will not change it.

We want to have a failing test to start with, so the test file now looks
like:
`tests/Fixer/Comment/RemoveCommentsFixerTest.php`
```php
<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Tests\Fixer\Comment;

use PhpCsFixer\Tests\Fixer\AbstractFixerTestBase;

/**
 * @author Your name <your@email.com>
 *
 * @internal
 */
final class RemoveCommentsFixerTest extends AbstractFixerTestBase
{
    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases()
    {
        return [
            [
               '<?php echo "This should be changed"; ', // This is expected output
               '<?php echo "This should be changed"; /* Comment */', // This is input
            ],
        ];
    }
}
```


### Step 3 - Implement your solution

You have defined the behavior of your fixer in tests. Now it is time to
implement it.

First, we need to create one method to describe what this fixer does:
`src/Fixer/Comment/RemoveCommentsFixer.php`:
```php
final class RemoveCommentsFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Removes all comments of the code that are preceded by ";" (semicolon).', // Trailing dot is important. We thrive to use English grammar properly.
            [
                new CodeSample(
                    '<?php echo 123; /* Comment */'
                ),
            ]
        );
    }
}
```
Next, we need to update the `README.rst`.
Fortunately, PHP CS Fixer can help you here.
Execute the following command in your command shell:

`$ php php-cs-fixer readme > README.rst`

Next, we must filter what type of tokens we want to fix. Here, we are interested in code that contains `T_COMMENT` tokens:
`src/Fixer/Comment/RemoveCommentsFixer.php`:
```php
final class RemoveCommentsFixer extends AbstractFixer
{
    ...

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_COMMENT);
    }
}
```

For now, let us just make a fixer that applies no modification:
`src/Fixer/Comment/RemoveCommentsFixer.php`:
```php
class RemoveCommentsFixer extends AbstractFixer
{
    ...

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        // no action
    }
}
```

Run `$ phpunit tests/Fixer/Comment/RemoveCommentsFixerTest.php`.
You are going to see that the tests fails.

### Break
Now we have pretty much a cradle to work with. A file with a failing
test, and the fixer, that for now does not do anything.

How do fixers work? In the PHP CS Fixer, they work by iterating through
pieces of codes (each being a Token), and inspecting what exists before
and after that bit and making a decision, usually:

 * Adding code.
 * Modifying code.
 * Deleting code.
 * Ignoring code.

In our case, we want to find all comments, and foreach (pun intended)
one of them check if they are preceded by a semicolon symbol.

Now you need to do some reading, because all these symbols obey a list
defined by the PHP compiler. It is the ["List of Parser
Tokens"](https://php.net/manual/en/tokens.php).

Internally, PHP CS Fixer transforms some of PHP native tokens into custom
tokens through the use of [Transfomers](/src/Tokenizer/Transformer),
they aim to help you reason about the changes you may want to do in the
fixers.

So we can get to move forward, humor me in believing that comments have
one symbol name: `T_COMMENT`.

### Step 3 - Implement your solution - continuation.

We do not want all symbols to be analysed. Only `T_COMMENT`. So let us
iterate the token(s) we are interested in.
`src/Fixer/Comment/RemoveCommentsFixer.php`:
```php
final class RemoveCommentsFixer extends AbstractFixer
{
    ...

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(T_COMMENT)) {
                continue;
            }

            // need to figure out what to do here!
        }
    }
}
```

OK, now for each `T_COMMENT`, all we need to do is check if the previous
token is a semicolon.
`src/Fixer/Comment/RemoveCommentsFixer.php`:
```php
final class RemoveCommentsFixer extends AbstractFixer
{
    ...

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(T_COMMENT)) {
                continue;
            }

            $prevTokenIndex = $tokens->getPrevMeaningfulToken($index);
            $prevToken = $tokens[$prevTokenIndex];

            if ($prevToken->equals(';')) {
                $tokens->clearAt($index);
            }
        }
    }
}
```

So the fixer in the end looks like this:
```php
<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Fixer\Comment;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Your name <your@email.com>
 */
final class RemoveCommentsFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Removes all comments of the code that are preceded by ";" (semicolon).', // Trailing dot is important. We thrive to use English grammar properly.
            [
                new CodeSample(
                    '<?php echo 123; /* Comment */'
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_COMMENT);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens) {
        foreach($tokens as $index => $token){
            if (!$token->isGivenKind(T_COMMENT)) {
                continue;
            }

            $prevTokenIndex = $tokens->getPrevMeaningfulToken($index);
            $prevToken = $tokens[$prevTokenIndex];

            if ($prevToken->equals(';')) {
                $tokens->clearAt($index);
            }
        }
    }
}
```

### Step 4 - Format, Commit, PR.

Note that so far, we have not coded adhering to PSR-1/2. This is done on
purpose. For every commit you make, you must use PHP CS Fixer to fix
itself. Thus, on the command line call:

`$ php php-cs-fixer fix`

This will fix all the coding style mistakes.

After the final CS fix, you are ready to commit. Do it.

Now, go to GitHub and open a Pull Request.


### Step 5 - Peer review: it is all about code and community building.

Congratulations, you have made your first fixer. Be proud. Your work
will be reviewed carefully by PHP CS Fixer community.

The review usually flows like this:

1. People will check your code for common mistakes and logical
caveats. Usually, the person building a fixer is blind about some
behavior mistakes of fixers. Expect to write few more tests to cater for
the reviews.
2. People will discuss the relevance of your fixer. If it is
something that goes along with Symfony style standards, or PSR-1/PSR-2
standards, they will ask you to add it to existing ruleset.
3. People will also discuss whether your fixer is idempotent or not.
If they understand that your fixer must always run before or after a
certain fixer, they will ask you to override a method named
`getPriority()`. Do not be afraid of asking the reviewer for help on how
to do it.
4. People may ask you to rebase your code to unify commits or to get
rid of merge commits.
5. Go to 1 until no actions are needed anymore.

Your fixer will be incorporated in the next release.

# Congratulations! You have done it.



## Q&A

#### Why is not my PR merged yet?

PHP CS Fixer is used by many people, that expect it to be stable. So
sometimes, few PR are delayed a bit so to avoid cluttering at @dev
channel on composer.

Other possibility is that reviewers are giving time to other members of
PHP CS Fixer community to partake on the review debates of your fixer.

In any case, we care a lot about what you do and we want to see it being
part of the application as soon as possible.

#### Why am I asked to use `getPrevMeaningfulToken()` instead of `getPrevNonWhitespace()`?

The main difference is that `getPrevNonWhitespace()` ignores only
whitespaces (`T_WHITESPACE`), while `getPrevMeaningfulToken()` ignores
whitespaces and comments. And usually that is what you want. For
example:

```php
$a->/*comment*/func();
```

If you are inspecting `func()`, and you want to check whether this is
part of an object, if you use `getPrevNonWhitespace()` you are going to
get `/*comment*/`, which might belie your test. On the other hand, if
you use `getPrevMeaningfulToken()`, no matter if you have got a comment
or a whitespace, the returned token will always be `->`.
