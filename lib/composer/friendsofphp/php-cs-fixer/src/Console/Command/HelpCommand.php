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

namespace PhpCsFixer\Console\Command;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Console\Application;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerConfiguration\AliasedFixerOption;
use PhpCsFixer\FixerConfiguration\AllowedValueSubset;
use PhpCsFixer\FixerConfiguration\DeprecatedFixerOption;
use PhpCsFixer\FixerConfiguration\FixerOptionInterface;
use PhpCsFixer\FixerFactory;
use PhpCsFixer\Preg;
use PhpCsFixer\RuleSet;
use PhpCsFixer\Utils;
use Symfony\Component\Console\Command\HelpCommand as BaseHelpCommand;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * @author SpacePossum
 *
 * @internal
 */
final class HelpCommand extends BaseHelpCommand
{
    protected static $defaultName = 'help';

    /**
     * Returns help-copy suitable for console output.
     *
     * @return string
     */
    public static function getHelpCopy()
    {
        $template =
            <<<'EOF'
The <info>%command.name%</info> command tries to fix as much coding standards
problems as possible on a given file or files in a given directory and its subdirectories:

    <info>$ php %command.full_name% /path/to/dir</info>
    <info>$ php %command.full_name% /path/to/file</info>

By default <comment>--path-mode</comment> is set to ``override``, which means, that if you specify the path to a file or a directory via
command arguments, then the paths provided to a ``Finder`` in config file will be ignored. You can use <comment>--path-mode=intersection</comment>
to merge paths from the config file and from the argument:

    <info>$ php %command.full_name% --path-mode=intersection /path/to/dir</info>

The <comment>--format</comment> option for the output format. Supported formats are ``txt`` (default one), ``json``, ``xml``, ``checkstyle``, ``junit`` and ``gitlab``.

NOTE: the output for the following formats are generated in accordance with XML schemas

* ``junit`` follows the `JUnit xml schema from Jenkins </doc/junit-10.xsd>`_
* ``checkstyle`` follows the common `"checkstyle" xml schema </doc/checkstyle.xsd>`_

The <comment>--quiet</comment> Do not output any message.

The <comment>--verbose</comment> option will show the applied rules. When using the ``txt`` format it will also display progress notifications.

NOTE: if there is an error like "errors reported during linting after fixing", you can use this to be even more verbose for debugging purpose

* ``--verbose=0`` or no option: normal
* ``--verbose``, ``--verbose=1``, ``-v``: verbose
* ``--verbose=2``, ``-vv``: very verbose
* ``--verbose=3``, ``-vvv``: debug

The <comment>--rules</comment> option limits the rules to apply to the
project:

    <info>$ php %command.full_name% /path/to/project --rules=@PSR2</info>

By default the PSR1 and PSR2 rules are used.

The <comment>--rules</comment> option lets you choose the exact rules to
apply (the rule names must be separated by a comma):

    <info>$ php %command.full_name% /path/to/dir --rules=line_ending,full_opening_tag,indentation_type</info>

You can also blacklist the rules you don't want by placing a dash in front of the rule name, if this is more convenient,
using <comment>-name_of_fixer</comment>:

    <info>$ php %command.full_name% /path/to/dir --rules=-full_opening_tag,-indentation_type</info>

When using combinations of exact and blacklist rules, applying exact rules along with above blacklisted results:

    <info>$ php %command.full_name% /path/to/project --rules=@Symfony,-@PSR1,-blank_line_before_statement,strict_comparison</info>

Complete configuration for rules can be supplied using a ``json`` formatted string.

    <info>$ php %command.full_name% /path/to/project --rules='{"concat_space": {"spacing": "none"}}'</info>

The <comment>--dry-run</comment> flag will run the fixer without making changes to your files.

The <comment>--diff</comment> flag can be used to let the fixer output all the changes it makes.

The <comment>--diff-format</comment> option allows to specify in which format the fixer should output the changes it makes:

* <comment>udiff</comment>: unified diff format;
* <comment>sbd</comment>: Sebastianbergmann/diff format (default when using `--diff` without specifying `diff-format`).

The <comment>--allow-risky</comment> option (pass ``yes`` or ``no``) allows you to set whether risky rules may run. Default value is taken from config file.
A rule is considered risky if it could change code behaviour. By default no risky rules are run.

The <comment>--stop-on-violation</comment> flag stops the execution upon first file that needs to be fixed.

The <comment>--show-progress</comment> option allows you to choose the way process progress is rendered:

* <comment>none</comment>: disables progress output;
* <comment>run-in</comment>: [deprecated] simple single-line progress output;
* <comment>estimating</comment>: [deprecated] multiline progress output with number of files and percentage on each line. Note that with this option, the files list is evaluated before processing to get the total number of files and then kept in memory to avoid using the file iterator twice. This has an impact on memory usage so using this option is not recommended on very large projects;
* <comment>estimating-max</comment>: [deprecated] same as <comment>dots</comment>;
* <comment>dots</comment>: same as <comment>estimating</comment> but using all terminal columns instead of default 80.

If the option is not provided, it defaults to <comment>run-in</comment> unless a config file that disables output is used, in which case it defaults to <comment>none</comment>. This option has no effect if the verbosity of the command is less than <comment>verbose</comment>.

    <info>$ php %command.full_name% --verbose --show-progress=estimating</info>

The command can also read from standard input, in which case it won't
automatically fix anything:

    <info>$ cat foo.php | php %command.full_name% --diff -</info>

Finally, if you don't need BC kept on CLI level, you might use `PHP_CS_FIXER_FUTURE_MODE` to start using options that
would be default in next MAJOR release (unified differ, estimating, full-width progress indicator):

    <info>$ PHP_CS_FIXER_FUTURE_MODE=1 php %command.full_name% -v --diff</info>

Choose from the list of available rules:

%%%FIXERS_DETAILS%%%

The <comment>--dry-run</comment> option displays the files that need to be
fixed but without actually modifying them:

    <info>$ php %command.full_name% /path/to/code --dry-run</info>

Config file
-----------

Instead of using command line options to customize the rule, you can save the
project configuration in a <comment>.php_cs.dist</comment> file in the root directory of your project.
The file must return an instance of `PhpCsFixer\ConfigInterface` (<url>%%%CONFIG_INTERFACE_URL%%%</url>)
which lets you configure the rules, the files and directories that
need to be analyzed. You may also create <comment>.php_cs</comment> file, which is
the local configuration that will be used instead of the project configuration. It
is a good practice to add that file into your <comment>.gitignore</comment> file.
With the <comment>--config</comment> option you can specify the path to the
<comment>.php_cs</comment> file.

The example below will add two rules to the default list of PSR2 set rules:

    <?php

    $finder = PhpCsFixer\Finder::create()
        ->exclude('somedir')
        ->notPath('src/Symfony/Component/Translation/Tests/fixtures/resources.php')
        ->in(__DIR__)
    ;

    return PhpCsFixer\Config::create()
        ->setRules([
            '@PSR2' => true,
            'strict_param' => true,
            'array_syntax' => ['syntax' => 'short'],
        ])
        ->setFinder($finder)
    ;

    ?>

**NOTE**: ``exclude`` will work only for directories, so if you need to exclude file, try ``notPath``.
Both ``exclude`` and ``notPath`` methods accept only relative paths to the ones defined with the ``in`` method.

See `Symfony\Finder` (<url>https://symfony.com/doc/current/components/finder.html</url>)
online documentation for other `Finder` methods.

You may also use a blacklist for the rules instead of the above shown whitelist approach.
The following example shows how to use all ``Symfony`` rules but the ``full_opening_tag`` rule.

    <?php

    $finder = PhpCsFixer\Finder::create()
        ->exclude('somedir')
        ->in(__DIR__)
    ;

    return PhpCsFixer\Config::create()
        ->setRules([
            '@Symfony' => true,
            'full_opening_tag' => false,
        ])
        ->setFinder($finder)
    ;

    ?>

You may want to use non-linux whitespaces in your project. Then you need to
configure them in your config file.

    <?php

    return PhpCsFixer\Config::create()
        ->setIndent("\t")
        ->setLineEnding("\r\n")
    ;

    ?>

By using ``--using-cache`` option with ``yes`` or ``no`` you can set if the caching
mechanism should be used.

Caching
-------

The caching mechanism is enabled by default. This will speed up further runs by
fixing only files that were modified since the last run. The tool will fix all
files if the tool version has changed or the list of rules has changed.
Cache is supported only for tool downloaded as phar file or installed via
composer.

Cache can be disabled via ``--using-cache`` option or config file:

    <?php

    return PhpCsFixer\Config::create()
        ->setUsingCache(false)
    ;

    ?>

Cache file can be specified via ``--cache-file`` option or config file:

    <?php

    return PhpCsFixer\Config::create()
        ->setCacheFile(__DIR__.'/.php_cs.cache')
    ;

    ?>

Using PHP CS Fixer on CI
------------------------

Require ``friendsofphp/php-cs-fixer`` as a ``dev`` dependency:

    $ ./composer.phar require --dev friendsofphp/php-cs-fixer

Then, add the following command to your CI:

%%%CI_INTEGRATION%%%

Where ``$COMMIT_RANGE`` is your range of commits, e.g. ``$TRAVIS_COMMIT_RANGE`` or ``HEAD~..HEAD``.

Exit code
---------

Exit code is built using following bit flags:

*  0 - OK.
*  1 - General error (or PHP minimal requirement not matched).
*  4 - Some files have invalid syntax (only in dry-run mode).
*  8 - Some files need fixing (only in dry-run mode).
* 16 - Configuration error of the application.
* 32 - Configuration error of a Fixer.
* 64 - Exception raised within the application.

(Applies to exit code of the ``fix`` command only)
EOF
        ;

        return strtr($template, [
            '%%%CONFIG_INTERFACE_URL%%%' => sprintf(
                'https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/v%s/src/ConfigInterface.php',
                self::getLatestReleaseVersionFromChangeLog()
            ),
            '%%%CI_INTEGRATION%%%' => implode("\n", array_map(
                static function ($line) { return '    $ '.$line; },
                \array_slice(file(__DIR__.'/../../../ci-integration.sh', FILE_IGNORE_NEW_LINES), 3)
            )),
            '%%%FIXERS_DETAILS%%%' => self::getFixersHelp(),
        ]);
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    public static function toString($value)
    {
        if (\is_array($value)) {
            // Output modifications:
            // - remove new-lines
            // - combine multiple whitespaces
            // - switch array-syntax to short array-syntax
            // - remove whitespace at array opening
            // - remove trailing array comma and whitespace at array closing
            // - remove numeric array indexes
            static $replaces = [
                ['#\r|\n#', '#\s{1,}#', '#array\s*\((.*)\)#s', '#\[\s+#', '#,\s*\]#', '#\d+\s*=>\s*#'],
                ['', ' ', '[$1]', '[', ']', ''],
            ];

            $str = var_export($value, true);
            do {
                $strNew = Preg::replace(
                    $replaces[0],
                    $replaces[1],
                    $str
                );

                if ($strNew === $str) {
                    break;
                }

                $str = $strNew;
            } while (true);
        } else {
            $str = var_export($value, true);
        }

        return Preg::replace('/\bNULL\b/', 'null', $str);
    }

    /**
     * Returns the allowed values of the given option that can be converted to a string.
     *
     * @return null|array
     */
    public static function getDisplayableAllowedValues(FixerOptionInterface $option)
    {
        $allowed = $option->getAllowedValues();

        if (null !== $allowed) {
            $allowed = array_filter($allowed, static function ($value) {
                return !($value instanceof \Closure);
            });

            usort($allowed, static function ($valueA, $valueB) {
                if ($valueA instanceof AllowedValueSubset) {
                    return -1;
                }

                if ($valueB instanceof AllowedValueSubset) {
                    return 1;
                }

                return strcasecmp(
                    self::toString($valueA),
                    self::toString($valueB)
                );
            });

            if (0 === \count($allowed)) {
                $allowed = null;
            }
        }

        return $allowed;
    }

    /**
     * @throws \RuntimeException when failing to parse the change log file
     *
     * @return string
     */
    public static function getLatestReleaseVersionFromChangeLog()
    {
        static $version = null;

        if (null !== $version) {
            return $version;
        }

        $changelogFile = self::getChangeLogFile();
        if (null === $changelogFile) {
            $version = Application::VERSION;

            return $version;
        }

        $changelog = @file_get_contents($changelogFile);
        if (false === $changelog) {
            $error = error_get_last();

            throw new \RuntimeException(sprintf(
                'Failed to read content of the changelog file "%s".%s',
                $changelogFile,
                $error ? ' '.$error['message'] : ''
            ));
        }

        for ($i = Application::getMajorVersion(); $i > 0; --$i) {
            if (1 === Preg::match('/Changelog for v('.$i.'.\d+.\d+)/', $changelog, $matches)) {
                $version = $matches[1];

                break;
            }
        }

        if (null === $version) {
            throw new \RuntimeException(sprintf('Failed to parse changelog data of "%s".', $changelogFile));
        }

        return $version;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $output->getFormatter()->setStyle('url', new OutputFormatterStyle('blue'));
    }

    /**
     * @return null|string
     */
    private static function getChangeLogFile()
    {
        $changelogFile = __DIR__.'/../../../CHANGELOG.md';

        return is_file($changelogFile) ? $changelogFile : null;
    }

    /**
     * @return string
     */
    private static function getFixersHelp()
    {
        $help = '';
        $fixerFactory = new FixerFactory();
        /** @var AbstractFixer[] $fixers */
        $fixers = $fixerFactory->registerBuiltInFixers()->getFixers();

        // sort fixers by name
        usort(
            $fixers,
            static function (FixerInterface $a, FixerInterface $b) {
                return strcmp($a->getName(), $b->getName());
            }
        );

        $ruleSets = [];
        foreach (RuleSet::create()->getSetDefinitionNames() as $setName) {
            $ruleSets[$setName] = new RuleSet([$setName => true]);
        }

        $getSetsWithRule = static function ($rule) use ($ruleSets) {
            $sets = [];

            foreach ($ruleSets as $setName => $ruleSet) {
                if ($ruleSet->hasRule($rule)) {
                    $sets[] = $setName;
                }
            }

            return $sets;
        };

        $count = \count($fixers) - 1;
        foreach ($fixers as $i => $fixer) {
            $sets = $getSetsWithRule($fixer->getName());

            $description = $fixer->getDefinition()->getSummary();

            if ($fixer instanceof DeprecatedFixerInterface) {
                $successors = $fixer->getSuccessorsNames();
                $message = [] === $successors
                    ? 'will be removed on next major version'
                    : sprintf('use %s instead', Utils::naturalLanguageJoinWithBackticks($successors));
                $description .= sprintf(' DEPRECATED: %s.', $message);
            }

            $description = implode("\n   | ", self::wordwrap(
                Preg::replace('/(`.+?`)/', '<info>$1</info>', $description),
                72
            ));

            if (!empty($sets)) {
                $help .= sprintf(" * <comment>%s</comment> [%s]\n   | %s\n", $fixer->getName(), implode(', ', $sets), $description);
            } else {
                $help .= sprintf(" * <comment>%s</comment>\n   | %s\n", $fixer->getName(), $description);
            }

            if ($fixer->isRisky()) {
                $help .= sprintf(
                    "   | *Risky rule: %s.*\n",
                    Preg::replace(
                        '/(`.+?`)/',
                        '<info>$1</info>',
                        lcfirst(Preg::replace('/\.$/', '', $fixer->getDefinition()->getRiskyDescription()))
                    )
                );
            }

            if ($fixer instanceof ConfigurationDefinitionFixerInterface) {
                $configurationDefinition = $fixer->getConfigurationDefinition();
                $configurationDefinitionOptions = $configurationDefinition->getOptions();
                if (\count($configurationDefinitionOptions)) {
                    $help .= "   |\n   | Configuration options:\n";

                    usort(
                        $configurationDefinitionOptions,
                        static function (FixerOptionInterface $optionA, FixerOptionInterface $optionB) {
                            return strcmp($optionA->getName(), $optionB->getName());
                        }
                    );

                    foreach ($configurationDefinitionOptions as $option) {
                        $line = '<info>'.OutputFormatter::escape($option->getName()).'</info>';

                        $allowed = self::getDisplayableAllowedValues($option);
                        if (null !== $allowed) {
                            foreach ($allowed as &$value) {
                                if ($value instanceof AllowedValueSubset) {
                                    $value = 'a subset of <comment>'.self::toString($value->getAllowedValues()).'</comment>';
                                } else {
                                    $value = '<comment>'.self::toString($value).'</comment>';
                                }
                            }
                        } else {
                            $allowed = array_map(
                                static function ($type) {
                                    return '<comment>'.$type.'</comment>';
                                },
                                $option->getAllowedTypes()
                            );
                        }

                        if (null !== $allowed) {
                            $line .= ' ('.implode(', ', $allowed).')';
                        }

                        $line .= ': '.Preg::replace(
                            '/(`.+?`)/',
                            '<info>$1</info>',
                            lcfirst(Preg::replace('/\.$/', '', OutputFormatter::escape($option->getDescription())))
                        ).'; ';
                        if ($option->hasDefault()) {
                            $line .= 'defaults to <comment>'.self::toString($option->getDefault()).'</comment>';
                        } else {
                            $line .= 'required';
                        }

                        if ($option instanceof DeprecatedFixerOption) {
                            $line .= '. DEPRECATED: '.Preg::replace(
                                '/(`.+?`)/',
                                '<info>$1</info>',
                                lcfirst(Preg::replace('/\.$/', '', OutputFormatter::escape($option->getDeprecationMessage())))
                            );
                        }

                        if ($option instanceof AliasedFixerOption) {
                            $line .= '; DEPRECATED alias: <comment>'.$option->getAlias().'</comment>';
                        }

                        foreach (self::wordwrap($line, 72) as $index => $line) {
                            $help .= (0 === $index ? '   | - ' : '   |   ').$line."\n";
                        }
                    }
                }
            } elseif ($fixer instanceof ConfigurableFixerInterface) {
                $help .= "   | *Configurable rule.*\n";
            }

            if ($count !== $i) {
                $help .= "\n";
            }
        }

        // prevent "\</foo>" from being rendered as an escaped literal style tag
        return Preg::replace('#\\\\(</.*?>)#', '<<$1', $help);
    }

    /**
     * Wraps a string to the given number of characters, ignoring style tags.
     *
     * @param string $string
     * @param int    $width
     *
     * @return string[]
     */
    private static function wordwrap($string, $width)
    {
        $result = [];
        $currentLine = 0;
        $lineLength = 0;
        foreach (explode(' ', $string) as $word) {
            $wordLength = \strlen(Preg::replace('~</?(\w+)>~', '', $word));
            if (0 !== $lineLength) {
                ++$wordLength; // space before word
            }

            if ($lineLength + $wordLength > $width) {
                ++$currentLine;
                $lineLength = 0;
            }

            $result[$currentLine][] = $word;
            $lineLength += $wordLength;
        }

        return array_map(static function ($line) {
            return implode(' ', $line);
        }, $result);
    }
}
