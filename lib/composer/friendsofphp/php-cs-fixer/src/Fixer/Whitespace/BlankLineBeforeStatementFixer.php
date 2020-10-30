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

namespace PhpCsFixer\Fixer\Whitespace;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\AllowedValueSubset;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * @author Andreas Möller <am@localheinz.com>
 * @author SpacePossum
 */
final class BlankLineBeforeStatementFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface, WhitespacesAwareFixerInterface
{
    /**
     * @var array
     */
    private static $tokenMap = [
        'break' => T_BREAK,
        'case' => T_CASE,
        'continue' => T_CONTINUE,
        'declare' => T_DECLARE,
        'default' => T_DEFAULT,
        'die' => T_EXIT,
        'do' => T_DO,
        'exit' => T_EXIT,
        'for' => T_FOR,
        'foreach' => T_FOREACH,
        'goto' => T_GOTO,
        'if' => T_IF,
        'include' => T_INCLUDE,
        'include_once' => T_INCLUDE_ONCE,
        'require' => T_REQUIRE,
        'require_once' => T_REQUIRE_ONCE,
        'return' => T_RETURN,
        'switch' => T_SWITCH,
        'throw' => T_THROW,
        'try' => T_TRY,
        'while' => T_WHILE,
        'yield' => T_YIELD,
    ];

    /**
     * @var array
     */
    private $fixTokenMap = [];

    /**
     * {@inheritdoc}
     */
    public function configure(array $configuration = null)
    {
        parent::configure($configuration);

        $this->fixTokenMap = [];

        foreach ($this->configuration['statements'] as $key) {
            $this->fixTokenMap[$key] = self::$tokenMap[$key];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'An empty line feed must precede any configured statement.',
            [
                new CodeSample(
                    '<?php
function A() {
    echo 1;
    return 1;
}
'
                ),
                new CodeSample(
                    '<?php
switch ($foo) {
    case 42:
        $bar->process();
        break;
    case 44:
        break;
}
',
                    [
                        'statements' => ['break'],
                    ]
                ),
                new CodeSample(
                    '<?php
foreach ($foo as $bar) {
    if ($bar->isTired()) {
        $bar->sleep();
        continue;
    }
}
',
                    [
                        'statements' => ['continue'],
                    ]
                ),
                new CodeSample(
                    '<?php
if ($foo === false) {
    die(0);
} else {
    $bar = 9000;
    die(1);
}
',
                    [
                        'statements' => ['die'],
                    ]
                ),
                new CodeSample(
                    '<?php
$i = 0;
do {
    echo $i;
} while ($i > 0);
',
                    [
                        'statements' => ['do'],
                    ]
                ),
                new CodeSample(
                    '<?php
if ($foo === false) {
    exit(0);
} else {
    $bar = 9000;
    exit(1);
}
',
                    [
                        'statements' => ['exit'],
                    ]
                ),
                new CodeSample(
                    '<?php
a:

if ($foo === false) {
    goto a;
} else {
    $bar = 9000;
    goto b;
}
',
                    [
                        'statements' => ['goto'],
                    ]
                ),
                new CodeSample(
                    '<?php
$a = 9000;
if (true) {
    $foo = $bar;
}
',
                    [
                        'statements' => ['if'],
                    ]
                ),
                new CodeSample(
                    '<?php

if (true) {
    $foo = $bar;
    return;
}
',
                    [
                        'statements' => ['return'],
                    ]
                ),
                new CodeSample(
                    '<?php
$a = 9000;
switch ($a) {
    case 42:
        break;
}
',
                    [
                        'statements' => ['switch'],
                    ]
                ),
                new CodeSample(
                    '<?php
if (null === $a) {
    $foo->bar();
    throw new \UnexpectedValueException("A cannot be null");
}
',
                    [
                        'statements' => ['throw'],
                    ]
                ),
                new CodeSample(
                    '<?php
$a = 9000;
try {
    $foo->bar();
} catch (\Exception $exception) {
    $a = -1;
}
',
                    [
                        'statements' => ['try'],
                    ]
                ),
                new CodeSample(
                    '<?php

if (true) {
    $foo = $bar;
    yield $foo;
}
',
                    [
                        'statements' => ['yield'],
                    ]
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run after NoExtraBlankLinesFixer, NoUselessReturnFixer, ReturnAssignmentFixer.
     */
    public function getPriority()
    {
        return -21;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound(array_values($this->fixTokenMap));
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $lineEnding = $this->whitespacesConfig->getLineEnding();
        $tokenKinds = array_values($this->fixTokenMap);
        $analyzer = new TokensAnalyzer($tokens);

        for ($index = 0, $limit = $tokens->count(); $index < $limit; ++$index) {
            $token = $tokens[$index];

            if (!$token->isGivenKind($tokenKinds) || ($token->isGivenKind(T_WHILE) && $analyzer->isWhilePartOfDoWhile($index))) {
                continue;
            }

            $prevNonWhitespaceToken = $tokens[$tokens->getPrevNonWhitespace($index)];

            if (!$prevNonWhitespaceToken->equalsAny([';', '}'])) {
                continue;
            }

            $prevIndex = $index - 1;
            $prevToken = $tokens[$prevIndex];

            if ($prevToken->isWhitespace()) {
                $countParts = substr_count($prevToken->getContent(), "\n");

                if (0 === $countParts) {
                    $tokens[$prevIndex] = new Token([T_WHITESPACE, rtrim($prevToken->getContent(), " \t").$lineEnding.$lineEnding]);
                } elseif (1 === $countParts) {
                    $tokens[$prevIndex] = new Token([T_WHITESPACE, $lineEnding.$prevToken->getContent()]);
                }
            } else {
                $tokens->insertAt($index, new Token([T_WHITESPACE, $lineEnding.$lineEnding]));

                ++$index;
                ++$limit;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('statements', 'List of statements which must be preceded by an empty line.'))
                ->setAllowedTypes(['array'])
                ->setAllowedValues([new AllowedValueSubset(array_keys(self::$tokenMap))])
                ->setDefault([
                    'break',
                    'continue',
                    'declare',
                    'return',
                    'throw',
                    'try',
                ])
                ->getOption(),
        ]);
    }
}
